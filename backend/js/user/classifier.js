/**
 * classifier.js - Aturan klasifikasi kesesuaian pelamar (simulasi Decision Tree).
 */
(function (global) {
  'use strict';

  const Paskarir = (global.Paskarir = global.Paskarir || {});

  /** Tingkat pendidikan; angka lebih besar = lebih tinggi. */
  const EDUCATION_LEVEL = { 'SMA / SMK': 1, 'D3': 2, 'S1 / D4': 3, 'S2': 4 };
  const SKILL_PASS_RATIO = 0.5;

  /** Data lowongan contoh (sama dengan halaman Daftar Lowongan). */
  const VACANCIES = [
    { nama: 'Staf Pemrograman', keterampilan: ['Pemrograman', 'Basis Data'], pendidikan: 'S1 / D4', pengalaman: 2 },
    { nama: 'Staf Administrasi', keterampilan: ['Dokumen', 'Komunikasi'], pendidikan: 'D3', pengalaman: 1 },
    { nama: 'Asisten Operasional', keterampilan: ['Komunikasi', 'Excel'], pendidikan: 'SMA / SMK', pengalaman: 0 },
    { nama: 'Analis Data', keterampilan: ['Basis Data', 'Analisis'], pendidikan: 'S1 / D4', pengalaman: 2 },
    { nama: 'Admin Perkantoran', keterampilan: ['Dokumen', 'Excel'], pendidikan: 'D3', pengalaman: 1 },
    { nama: 'Staf Layanan', keterampilan: ['Komunikasi', 'Ramah'], pendidikan: 'SMA / SMK', pengalaman: 0 },
  ];

  /** @returns {object|undefined} lowongan berdasarkan nama (tidak peduli huruf besar/kecil) */
  const findVacancy = (name) =>
    VACANCIES.find((vacancy) => vacancy.nama.toLowerCase() === String(name).trim().toLowerCase());

  /** Rasio keterampilan pelamar yang cocok dengan syarat lowongan (0..1). */
  const skillMatchRatio = (applicantSkills, requiredSkills) => {
    const owned = applicantSkills.map((skill) => skill.toLowerCase());
    const matched = requiredSkills.filter((skill) => owned.includes(skill.toLowerCase()));
    return requiredSkills.length === 0 ? 1 : matched.length / requiredSkills.length;
  };

  /**
   * Menghitung hasil klasifikasi satu pelamar terhadap satu lowongan.
   *
   * @param {{keterampilan: string[], pendidikan: string, pengalaman: number, sertifikasi: string}} applicant
   * @param {{nama: string, keterampilan: string[], pendidikan: string, pengalaman: number}} vacancy
   * @returns {{label: string, sesuai: boolean, skor: number, alasan: {ok: boolean, teks: string}[]}}
   */
  const classify = (applicant, vacancy) => {
    const skillRatio = skillMatchRatio(applicant.keterampilan, vacancy.keterampilan);
    const skillOk = skillRatio >= SKILL_PASS_RATIO;
    const educationOk = EDUCATION_LEVEL[applicant.pendidikan] >= EDUCATION_LEVEL[vacancy.pendidikan];
    const experienceOk = applicant.pengalaman >= vacancy.pengalaman;

    const experienceRatio = vacancy.pengalaman === 0 ? 1 : Math.min(1, applicant.pengalaman / vacancy.pengalaman);
    let score = 50 * skillRatio + 25 * (educationOk ? 1 : 0) + 25 * experienceRatio;
    if (applicant.sertifikasi === 'ya') score += 5; // bonus sertifikasi
    score = Math.min(100, Math.round(score));

    const sesuai = skillOk && educationOk && experienceOk;
    return {
      label: sesuai ? 'SESUAI' : 'TIDAK SESUAI',
      sesuai,
      skor: score,
      alasan: [
        { ok: skillOk, teks: skillOk ? 'Keterampilan utama cocok dengan syarat' : 'Keterampilan utama belum cocok dengan syarat' },
        { ok: experienceOk, teks: experienceOk ? 'Pengalaman memenuhi minimal' : `Pengalaman kurang dari minimal (${vacancy.pengalaman} tahun)` },
        { ok: educationOk, teks: educationOk ? 'Pendidikan memenuhi syarat' : `Pendidikan di bawah minimal (${vacancy.pendidikan})` },
      ],
    };
  };

  /**
   * Menyusun rekomendasi: lowongan lain dengan skor tertinggi.
   * @returns {{nama: string, skor: number}[]} maksimal `limit` lowongan
   */
  const recommend = (applicant, excludedName, limit = 3) =>
    VACANCIES
      .filter((vacancy) => vacancy.nama !== excludedName)
      .map((vacancy) => ({ nama: vacancy.nama, skor: classify(applicant, vacancy).skor }))
      .sort((a, b) => b.skor - a.skor)
      .slice(0, limit);

  Paskarir.Classifier = { EDUCATION_LEVEL, VACANCIES, findVacancy, skillMatchRatio, classify, recommend };
})(window);
