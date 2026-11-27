<?php
const DT_LABEL_SESUAI = 'Siap Kerja/Lanjutan'; // kelas ini dianggap SESUAI; kelas lain dianggap TIDAK SESUAI
/** Mesin ID3 PasKarir. Nilai skor dikelompokkan: <60 rendah, 60-79 sedang, >=80 tinggi. */
function dt_entropy(array $rows): float {
  $n=count($rows); if (!$n) return 0.0; $counts=[];
  foreach($rows as $r){$k=$r['target'];$counts[$k]=($counts[$k]??0)+1;}
  $e=0.0; foreach($counts as $c){$p=$c/$n;$e-=$p*log($p,2);} return $e;
}
function dt_features(array $r): array {
  $bin=static function($v){$v=(int)$v;return $v<60?'Rendah (<60)':($v<80?'Sedang (60-79)':'Tinggi (80-100)');};
  return ['Usia'=>((int)$r['usia']<21?'Di bawah 21':((int)$r['usia']<=25?'21–25':'Di atas 25')), 'Pendidikan'=>$r['pendidikan'], 'Pengalaman Kerja'=>$r['pengalaman_kerja'], 'Bidang Minat'=>$r['bidang_minat'], 'Skor Komputer'=>$bin($r['skor_komputer']), 'Skor Komunikasi'=>$bin($r['skor_komunikasi']), 'Skor Teknis'=>$bin($r['skor_teknis'])];
}
function dt_rows(PDO $pdo): array {
  $raw=$pdo->query('SELECT * FROM dataset_pelatihan ORDER BY kode')->fetchAll();
  $out=[]; foreach($raw as $r){$out[]=['kode'=>$r['kode'],'target'=>$r['kebutuhan_pelatihan'],'features'=>dt_features($r),'raw'=>$r];} return $out;
}
function dt_counts(array $rows): array {$c=[];foreach($rows as $r){$k=$r['target'];$c[$k]=($c[$k]??0)+1;}ksort($c);return $c;}
function dt_gain(array $rows,string $feature): float {
  $base=dt_entropy($rows);$groups=[];foreach($rows as $r){$v=$r['features'][$feature];$groups[$v][]=$r;}
  $weighted=0.0;$n=count($rows);foreach($groups as $g){$weighted+=count($g)/$n*dt_entropy($g);}return $base-$weighted;
}
function dt_build(array $rows,array $features,int $depth=0): array {
  $counts=dt_counts($rows);arsort($counts);$majority=array_key_first($counts);
  if(count($counts)<=1 || !$features || $depth>=6 || count($rows)<3) return ['leaf'=>$majority,'n'=>count($rows),'counts'=>$counts];
  $best=null;$bestGain=-1;foreach($features as $f){$g=dt_gain($rows,$f);if($g>$bestGain+1e-12){$bestGain=$g;$best=$f;}}
  if($best===null || $bestGain<1e-10) return ['leaf'=>$majority,'n'=>count($rows),'counts'=>$counts];
  $groups=[];foreach($rows as $r){$groups[$r['features'][$best]][]=$r;}
  $remaining=array_values(array_diff($features,[$best]));$children=[];ksort($groups);
  foreach($groups as $value=>$group){$children[$value]=dt_build($group,$remaining,$depth+1);}
  return ['feature'=>$best,'gain'=>$bestGain,'n'=>count($rows),'counts'=>$counts,'children'=>$children,'default'=>$majority];
}
function dt_predict(array $tree,array $features): string {
  if(isset($tree['leaf']))return $tree['leaf'];$f=$tree['feature'];$v=$features[$f]??null;
  if($v===null || !isset($tree['children'][$v]))return $tree['default'];return dt_predict($tree['children'][$v],$features);
}
function dt_split(array $rows): array {
  // Stratified 80:20, deterministik: 12 data latih + 3 data uji per kelas.
  $groups=[];foreach($rows as $r)$groups[$r['target']][]=$r;$train=[];$test=[];
  foreach($groups as $g){$n=count($g);$cut=max(1,(int)floor($n*0.8));foreach($g as $i=>$r){if($i<$cut)$train[]=$r;else $test[]=$r;}}
  return [$train,$test];
}
function dt_evaluate(array $rows): array {
  [$train,$test]=dt_split($rows);$features=array_keys($rows[0]['features']??[]);$tree=dt_build($train,$features);$labels=array_keys(dt_counts($rows));$matrix=[];foreach($labels as $a){foreach($labels as $b)$matrix[$a][$b]=0;}
  $correct=0;foreach($test as $r){$p=dt_predict($tree,$r['features']);$matrix[$r['target']][$p]++;if($p===$r['target'])$correct++;}
  $tp=$fn=$fp=$tn=0;foreach($test as $r){$p=dt_predict($tree,$r['features']);$aS=$r['target']===DT_LABEL_SESUAI;$pS=$p===DT_LABEL_SESUAI;if($aS&&$pS)$tp++;elseif($aS)$fn++;elseif($pS)$fp++;else $tn++;}
  $biner=['tp'=>$tp,'fn'=>$fn,'fp'=>$fp,'tn'=>$tn,'total'=>count($test),'accuracy'=>count($test)?($tp+$tn)/count($test):0];
  return ['tree'=>$tree,'train'=>$train,'test'=>$test,'labels'=>$labels,'matrix'=>$matrix,'correct'=>$correct,'accuracy'=>$biner['accuracy'],'biner'=>$biner,'entropy'=>dt_entropy($train),'gains'=>array_map(fn($f)=>['feature'=>$f,'gain'=>dt_gain($train,$f)],$features)];
}
function dt_tree_html(array $tree): string {
  if(isset($tree['leaf']))return '<div class="tree-leaf">'.htmlspecialchars($tree['leaf'],ENT_QUOTES,'UTF-8').' <small>('.(int)$tree['n'].' data)</small></div>';
  $s='<div class="tree-node"><strong>'.htmlspecialchars($tree['feature'],ENT_QUOTES,'UTF-8').'</strong><small>Gain '.number_format($tree['gain'],4).'</small><div class="tree-branches">';
  foreach($tree['children'] as $v=>$child){$s.='<section><span>'.htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8').'</span>'.dt_tree_html($child).'</section>';}
  return $s.'</div></div>';
}
