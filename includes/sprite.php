<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <defs>
    <radialGradient id="g-rose" cx="45%" cy="40%" r="60%">
      <stop offset="0" stop-color="#E9C0B9"/>
      <stop offset="1" stop-color="#C98F88"/>
    </radialGradient>
    <radialGradient id="g-peony" cx="50%" cy="45%" r="60%">
      <stop offset="0" stop-color="#FBEDE7"/>
      <stop offset="1" stop-color="#EBC3BB"/>
    </radialGradient>
    <linearGradient id="g-leaf" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#9AAE92"/>
      <stop offset="1" stop-color="#6F8468"/>
    </linearGradient>

    <symbol id="rose" viewBox="-50 -50 100 100">
      <path d="M-44 4C-46-28-16-48 12-44 40-40 50-12 44 14 38 40 8 50-18 44-38 38-43 22-44 4Z" fill="url(#g-rose)"/>
      <path d="M-30-6C-28-30-2-38 18-30 36-22 38 2 30 18 20 36-6 38-22 28-30 20-31 8-30-6Z" fill="#D9A6A0"/>
      <path d="M-18-2C-16-20 4-26 16-16 28-6 24 12 12 20-2 28-20 18-18-2Z" fill="#CF978F"/>
      <path d="M-2 2C2-6 12-4 12 4 12 12 2 16-6 12-14 6-12-8-2-12 10-16 20-6 18 6" fill="none" stroke="#A9716B" stroke-width="2.4" stroke-linecap="round"/>
      <path d="M-34 10C-26 24-10 30 6 28M24-26C34-16 36-2 32 10" fill="none" stroke="#B9817A" stroke-width="1.6" stroke-linecap="round" opacity=".7"/>
    </symbol>

    <symbol id="peony" viewBox="-50 -50 100 100">
      <g fill="url(#g-peony)" stroke="#E3B2AA" stroke-width=".8">
        <ellipse rx="17" ry="27" transform="translate(0 -22)"/>
        <ellipse rx="17" ry="27" transform="rotate(60) translate(0 -22)"/>
        <ellipse rx="17" ry="27" transform="rotate(120) translate(0 -22)"/>
        <ellipse rx="17" ry="27" transform="rotate(180) translate(0 -22)"/>
        <ellipse rx="17" ry="27" transform="rotate(240) translate(0 -22)"/>
        <ellipse rx="17" ry="27" transform="rotate(300) translate(0 -22)"/>
      </g>
      <g fill="#F6DCD5" stroke="#E5B7AF" stroke-width=".7">
        <ellipse rx="12" ry="18" transform="rotate(30) translate(0 -13)"/>
        <ellipse rx="12" ry="18" transform="rotate(102) translate(0 -13)"/>
        <ellipse rx="12" ry="18" transform="rotate(174) translate(0 -13)"/>
        <ellipse rx="12" ry="18" transform="rotate(246) translate(0 -13)"/>
        <ellipse rx="12" ry="18" transform="rotate(318) translate(0 -13)"/>
      </g>
      <circle r="9" fill="#F3E7BE"/>
      <g fill="#C6A66B">
        <circle cx="-4" cy="-3" r="1.6"/><circle cx="3" cy="-4" r="1.6"/><circle cx="5" cy="2" r="1.6"/>
        <circle cx="-1" cy="4" r="1.6"/><circle cx="-5" cy="2" r="1.4"/><circle cx="0" cy="-0" r="1.4"/>
      </g>
    </symbol>

    <symbol id="leaf" viewBox="0 0 64 26">
      <path d="M1 13C16-1 42-2 63 13 42 28 16 27 1 13Z" fill="url(#g-leaf)"/>
      <path d="M3 13C22 11 42 11 60 13" fill="none" stroke="#5F7359" stroke-width="1"/>
    </symbol>

    <symbol id="breath" viewBox="0 0 60 60">
      <path d="M30 60C30 44 26 30 14 18M30 44C34 32 42 24 50 16M28 34C24 26 26 14 30 6" fill="none" stroke="#9AAE92" stroke-width="1.1"/>
      <g fill="#FFFDF8" stroke="#E7DCC8" stroke-width=".6">
        <circle cx="14" cy="17" r="3.2"/><circle cx="9" cy="21" r="2.4"/><circle cx="18" cy="12" r="2.4"/>
        <circle cx="50" cy="15" r="3.2"/><circle cx="54" cy="20" r="2.2"/><circle cx="46" cy="10" r="2.4"/>
        <circle cx="30" cy="5" r="3.2"/><circle cx="25" cy="8" r="2.2"/><circle cx="35" cy="9" r="2.2"/>
      </g>
    </symbol>

    <!-- Bingkai sudut: komposisi bunga di pojok kiri atas, dicerminkan via CSS untuk sudut lain -->
    <symbol id="floral-corner" viewBox="0 0 320 320">
      <path d="M0 40C60 50 120 40 190 8M40 0C50 70 40 140 6 210" fill="none" stroke="#7E9276" stroke-width="2.2" stroke-linecap="round"/>
      <use href="#leaf" x="150" y="2" width="70" height="28" transform="rotate(-18 185 16)"/>
      <use href="#leaf" x="190" y="-4" width="58" height="24" transform="rotate(-30 219 8)"/>
      <use href="#leaf" x="2" y="170" width="70" height="28" transform="rotate(100 37 184)"/>
      <use href="#leaf" x="-14" y="216" width="58" height="24" transform="rotate(112 15 228)"/>
      <use href="#leaf" x="96" y="86" width="64" height="26" transform="rotate(40 128 99)"/>
      <use href="#leaf" x="60" y="118" width="56" height="22" transform="rotate(70 88 129)"/>
      <use href="#breath" x="150" y="40" width="70" height="70"/>
      <use href="#breath" x="20" y="150" width="64" height="64" transform="rotate(-70 52 182)"/>
      <use href="#breath" x="200" y="6" width="52" height="52" transform="rotate(30 226 32)"/>
      <use href="#peony" x="10" y="8" width="130" height="130"/>
      <use href="#rose" x="118" y="12" width="74" height="74"/>
      <use href="#rose" x="6" y="118" width="70" height="70"/>
      <use href="#rose" x="92" y="78" width="46" height="46"/>
    </symbol>

    <!-- Rangkaian kecil untuk pemisah bagian -->
    <symbol id="floral-sprig" viewBox="0 0 240 60">
      <path d="M10 34C60 26 180 26 230 34" fill="none" stroke="#7E9276" stroke-width="1.4"/>
      <use href="#leaf" x="34" y="18" width="46" height="18" transform="rotate(-12 57 27)"/>
      <use href="#leaf" x="160" y="18" width="46" height="18" transform="rotate(192 183 27)"/>
      <use href="#breath" x="60" y="6" width="34" height="34"/>
      <use href="#breath" x="146" y="6" width="34" height="34"/>
      <use href="#rose" x="96" y="6" width="48" height="48"/>
    </symbol>

    <!-- Kelopak berjatuhan -->
    <symbol id="petal-a" viewBox="0 0 30 30">
      <path d="M15 2C22 6 27 14 22 22 19 27 11 27 8 22 3 14 8 6 15 2Z" fill="#E9BDB6"/>
      <path d="M15 5C14 12 14 18 15 25" fill="none" stroke="#D29890" stroke-width=".9" opacity=".7"/>
    </symbol>
    <symbol id="petal-b" viewBox="0 0 30 30">
      <path d="M4 16C6 7 16 3 25 6 27 14 22 25 12 27 7 25 4 21 4 16Z" fill="#F6DCD5"/>
      <path d="M7 22C12 16 17 11 23 8" fill="none" stroke="#E3B2AA" stroke-width=".9"/>
    </symbol>
    <symbol id="petal-leaf" viewBox="0 0 30 30">
      <path d="M3 15C10 5 22 4 27 15 22 26 10 25 3 15Z" fill="#9AAE92"/>
      <path d="M5 15H25" stroke="#6F8468" stroke-width=".8"/>
    </symbol>

    <!-- Ornamen geometris Islami: bintang delapan -->
    <symbol id="ornament" viewBox="0 0 200 40">
      <path d="M0 20H72M128 20H200" stroke="currentColor" stroke-width="1"/>
      <g fill="none" stroke="currentColor" stroke-width="1.2" transform="translate(100 20)">
        <rect x="-11" y="-11" width="22" height="22"/>
        <rect x="-11" y="-11" width="22" height="22" transform="rotate(45)"/>
        <circle r="4.5"/>
      </g>
      <g fill="currentColor"><circle cx="76" cy="20" r="2"/><circle cx="124" cy="20" r="2"/></g>
      <path d="M82 20l4-4 4 4-4 4zM110 20l4-4 4 4-4 4z" fill="none" stroke="currentColor" stroke-width="1"/>
    </symbol>

    <symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 22s7-6.3 7-12a7 7 0 1 0-14 0c0 5.7 7 12 7 12Z" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.6" fill="none" stroke="currentColor" stroke-width="1.6"/></symbol>
    <symbol id="i-cal" viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M3.5 10h17M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 7.5V12l3 2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></symbol>
    <symbol id="i-copy" viewBox="0 0 24 24"><rect x="8" y="8" width="12" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" fill="none" stroke="currentColor" stroke-width="1.6"/></symbol>
    <symbol id="i-music" viewBox="0 0 24 24"><path d="M9 18V6l11-2v12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="6.5" cy="18" r="2.5" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="17.5" cy="16" r="2.5" fill="none" stroke="currentColor" stroke-width="1.6"/></symbol>
    <symbol id="i-pause" viewBox="0 0 24 24"><path d="M8 5v14M16 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-mail" viewBox="0 0 24 24"><rect x="3" y="5.5" width="18" height="13" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="m3.5 7 8.5 6 8.5-6" fill="none" stroke="currentColor" stroke-width="1.6"/></symbol>
    <symbol id="i-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
    <symbol id="i-left" viewBox="0 0 24 24"><path d="m15 5-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-right" viewBox="0 0 24 24"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-gift" viewBox="0 0 24 24"><rect x="3.5" y="8" width="17" height="4" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M5 12v8h14v-8M12 8v12M12 8C10 4 6 4 6.5 6.5 7 8 12 8 12 8Zm0 0c2-4 6-4 5.5-1.5C17 8 12 8 12 8Z" fill="none" stroke="currentColor" stroke-width="1.6"/></symbol>
  </defs>
</svg>
