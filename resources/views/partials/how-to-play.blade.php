<div class="modal-backdrop" id="modal-how-to-play" style="display: none;">
    <section aria-label="Cara bermain" class="info-modal">
        <div class="modal-heading">
            <div>
                <span class="eyebrow">Panduan Resmi Permainan</span>
                <h2>Cara Bermain</h2>
            </div>
            <button type="button" aria-label="Tutup panduan" class="icon-button" id="btn-close-how-to-play">
                <x-icon name="x" />
            </button>
        </div>

        {{-- Tab Switcher --}}
        <div class="how-tabs-nav" style="padding: 20px 30px 0; display: flex; gap: 10px;">
            <button type="button" class="how-tab-btn active" id="tab-btn-guess" data-tab="guess">
                <span class="tab-badge">01</span> Cara Bermain — Guess Me!
            </button>
            <button type="button" class="how-tab-btn" id="tab-btn-growth" data-tab="growth">
                <span class="tab-badge">02</span> Cara Bermain — BYC Growth 100
            </button>
        </div>

        {{-- Tab 1: Guess Me! --}}
        <div class="how-tab-pane" id="tab-pane-guess" style="display: block; padding: 20px 30px 10px;">
            <article class="how-card" style="padding: 24px; background: var(--white); border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 4px 15px rgba(32, 48, 41, .04);">
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                    <span style="width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; background: var(--lime); color: var(--forest-dark); font: 800 18px 'Manrope', sans-serif;">01</span>
                    <div>
                        <h3 style="margin: 0; font: 800 22px 'Manrope', sans-serif; color: var(--forest-dark);">Guess Me! — Rules Permainan</h3>
                        <small style="color: var(--muted); font-size: 13px;">Uji ketepatan dan kecepatan menebak kata kunci rahasia</small>
                    </div>
                </div>
                <ol style="padding-left: 20px; margin: 0; color: var(--ink); line-height: 1.85; font-size: 15px;">
                    <li>Peserta dibagi menjadi <strong>Tim Red</strong> dan <strong>Tim Blue</strong>.</li>
                    <li>Host menampilkan gambar dan clue kepada kedua tim secara bersamaan.</li>
                    <li>Kedua tim berdiskusi dan menuliskan jawaban masing-masing pada secarik kertas.</li>
                    <li>Setelah jawaban dikumpulkan, host menekan tombol untuk menampilkan jawaban yang benar.</li>
                    <li>Tim yang menjawab benar memperoleh poin sesuai nominal skor ronde tersebut.</li>
                    <li>Jika kedua tim menjawab dengan benar, kedua tim sama-sama memperoleh poin ronde yang sama.</li>
                    <li>Host melanjutkan permainan ke ronde berikutnya dengan menekan tombol navigasi berikutnya.</li>
                </ol>
            </article>
        </div>

        {{-- Tab 2: BYC Growth 100 --}}
        <div class="how-tab-pane" id="tab-pane-growth" style="display: none; padding: 20px 30px 10px;">
            <article class="how-card" style="padding: 24px; background: var(--white); border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 4px 15px rgba(32, 48, 41, .04); max-height: 48vh; overflow-y: auto;">
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                    <span style="width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; background: var(--gold); color: var(--forest-dark); font: 800 18px 'Manrope', sans-serif;">02</span>
                    <div>
                        <h3 style="margin: 0; font: 800 22px 'Manrope', sans-serif; color: var(--forest-dark);">BYC GROWTH 100 — Rules Permainan</h3>
                        <small style="color: var(--muted); font-size: 13px;">Temukan jawaban survei teratas dan kumpulkan hingga 100 poin</small>
                    </div>
                </div>
                <ol style="padding-left: 20px; margin: 0; color: var(--ink); line-height: 1.8; font-size: 14px;">
                    <li>Peserta dibagi menjadi <strong>Tim Red</strong> dan <strong>Tim Blue</strong>.</li>
                    <li>Perwakilan kedua tim maju ke depan untuk menentukan tim yang bermain terlebih dahulu.</li>
                    <li>Host membacakan pertanyaan survei kepada kedua perwakilan.</li>
                    <li>Perwakilan yang lebih dahulu mengangkat tangan mendapat kesempatan pertama menjawab.</li>
                    <li>Jawaban dibandingkan dengan hasil survey yang ada di papan game.</li>
                    <li>Tim dengan jawaban yang memiliki ranking lebih tinggi mendapat kesempatan bermain utama.</li>
                    <li>Pertanyaan yang sama diberikan kepada anggota tim secara bergantian.</li>
                    <li>Jawaban yang tersedia pada survey akan dibuka oleh host dengan mengklik tile jawaban.</li>
                    <li>Jawaban yang tidak tersedia menghasilkan satu tanda silang (<span style="color: var(--red); font-weight: 800;">×</span>).</li>
                    <li>Permainan berlanjut hingga seluruh anggota tim mendapat giliran.</li>
                    <li>Jika masih ada jawaban yang belum terbuka, pertanyaan kembali diberikan kepada ketua tim.</li>
                    <li>Jika seluruh jawaban berhasil ditemukan, seluruh akumulasi poin ronde diberikan kepada tim tersebut.</li>
                    <li>Jika tim melakukan <strong>tiga kesalahan (3× cross)</strong>, kesempatan mencuri poin (steal) diberikan kepada tim lawan.</li>
                    <li>Tim lawan hanya memiliki <strong>satu kesempatan menjawab</strong>.</li>
                    <li>Jika jawaban tim lawan tersedia di survey, seluruh poin ronde diberikan kepada tim lawan.</li>
                    <li>Jika jawaban tim lawan tidak tersedia, poin tetap diberikan kepada tim sebelumnya.</li>
                    <li>Setelah ronde selesai, sisa jawaban yang belum terbuka dapat dibuka oleh host.</li>
                    <li>Host memberikan poin ke tim pemenang dan melanjutkan ke ronde berikutnya.</li>
                </ol>
            </article>
        </div>

        <div class="tip" style="margin: 10px 30px 20px;">
            <strong>Tips untuk host</strong>
            <p>Gunakan mode layar penuh (F11) agar seluruh peserta dapat melihat jalannya permainan dengan jelas. Pastikan skor diberikan secara teliti sesuai aturan masing-masing game.</p>
        </div>

        <button type="button" class="button button-primary" id="btn-start-playing" style="margin: 0 30px 26px; width: calc(100% - 60px);">
            Siap, mulai bermain
        </button>
    </section>
</div>
