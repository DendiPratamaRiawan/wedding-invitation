<?php
declare(strict_types=1);

/** Konten bawaan. Semua dapat diubah dari panel admin. */
function default_content(): array
{
    return [
        'basmalah' => 'بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ',
        'cover_title' => 'Undangan Pernikahan',
        'guest_prefix' => 'Bapak/Ibu',
        'salam_open' => "Assalamu'alaikum Warahmatullahi Wabarakatuh",
        'quote_arabic' => 'وَمِنْ اٰيٰتِهٖٓ اَنْ خَلَقَ لَكُمْ مِّنْ اَنْفُسِكُمْ اَزْوَاجًا لِّتَسْكُنُوْٓا اِلَيْهَا وَجَعَلَ بَيْنَكُمْ مَّوَدَّةً وَّرَحْمَةً',
        'quote_translation' => 'Dan di antara tanda-tanda (kebesaran)-Nya ialah Dia menciptakan pasangan-pasangan untukmu dari jenismu sendiri, agar kamu cenderung dan merasa tenteram kepadanya, dan Dia menjadikan di antaramu rasa kasih dan sayang.',
        'quote_source' => 'QS. Ar-Rum: 21',
        'groom_relation' => 'Putra dari',
        'bride_relation' => 'Putri dari',
        'groom_bio' => '',
        'bride_bio' => '',
        'groom_photo' => '',
        'bride_photo' => '',
        'couple_photo' => '',
        'closing_prayer_arabic' => 'بَارَكَ اللّٰهُ لَكَ وَبَارَكَ عَلَيْكَ وَجَمَعَ بَيْنَكُمَا فِيْ خَيْرٍ',
        'closing_prayer_translation' => '“Semoga Allah memberkahimu dalam suka maupun duka, dan semoga Allah mempersatukan kalian berdua dalam kebaikan.” (HR. Abu Dawud & At-Tirmidzi)',
        'salam_close' => "Wassalamu'alaikum Warahmatullahi Wabarakatuh",
        'closing_signature' => 'Kami yang berbahagia',
        'story' => [
            ['title' => 'Pertemuan Pertama', 'date_label' => '', 'text' => 'Ceritakan bagaimana kalian pertama kali bertemu.'],
            ['title' => 'Lamaran', 'date_label' => '', 'text' => 'Ceritakan momen lamaran.'],
        ],
        'gift' => [
            'intro' => 'Doa restu Bapak/Ibu/Saudara/i merupakan karunia yang sangat berarti bagi kami. Namun, apabila berkenan memberikan tanda kasih, dapat melalui:',
            'accounts' => [],
        ],
        'sections' => [
            'opening' => true,
            'couple' => true,
            'events' => true,
            'countdown' => true,
            'gallery' => true,
            'story' => false,
            'rsvp' => true,
            'wishes' => true,
            'gift' => false,
            'closing' => true,
            'music' => false,
        ],
        'rsvp_max_companions' => 1,
        'wishes_auto_approve' => false,
        'music_path' => '',
        // Musik mulai saat tamu menekan "Buka Undangan" (interaksi pengguna, bukan autoplay saat halaman dimuat)
        'music_on_open' => true,
        'wa_template' => "Assalamu'alaikum warahmatullahi wabarakatuh.\n\n"
            . "Dengan memohon rahmat dan ridha Allah SWT, kami bermaksud mengundang Bapak/Ibu/Saudara/i *[Nama Tamu]* untuk menghadiri pernikahan *[Nama Mempelai]*.\n\n"
            . "Informasi lengkap mengenai acara dapat dilihat melalui tautan berikut:\n[Tautan Undangan]\n\n"
            . "Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir serta memberikan doa restu.\n\n"
            . "Wassalamu'alaikum warahmatullahi wabarakatuh.",
    ];
}

/** Preset tema — semua berasal dari palet PRD, hanya penekanan warnanya yang berbeda. */
function theme_presets(): array
{
    return [
        'purnama' => ['label' => 'Purnama Sage (bawaan)', 'primary' => '#82977A', 'accent' => '#D9A6A0'],
        'mawar' => ['label' => 'Mawar Lembut', 'primary' => '#D9A6A0', 'accent' => '#82977A'],
        'emas' => ['label' => 'Champagne Malam', 'primary' => '#7E8F76', 'accent' => '#C6A66B'],
    ];
}

function default_theme(): array
{
    return ['preset' => 'purnama'];
}

function default_opening_text(): string
{
    return 'Dengan memohon rahmat dan ridha Allah SWT, kami bermaksud menyelenggarakan pernikahan putra-putri kami. Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu.';
}

function default_closing_text(): string
{
    return 'Merupakan suatu kebahagiaan dan kehormatan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu kepada kedua mempelai. Atas kehadiran dan doa restunya, kami ucapkan terima kasih.';
}
