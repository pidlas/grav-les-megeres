document.addEventListener('DOMContentLoaded', function() {
  const audio = document.getElementById('audio');
  const playPauseBtn = document.getElementById('audioPlayPauseBtn');
  const progress = document.getElementById('audioProgress');
  const time = document.getElementById('audioTime');
  const muteBtn = document.getElementById('audioMuteBtn');
  const volumeSlider = document.getElementById('audioVolumeSlider');
  
  let lastVolume = 1;

  if (!audio) return;

  function updateButton() {
    if (playPauseBtn) {
      if (audio.paused) {
        playPauseBtn.classList.remove('is-playing');
      } else {
        playPauseBtn.classList.add('is-playing');
      }
    }
  }

  function updateProgressBar() {
    if (!progress || !time) return;

    let percent = 0;
    if (audio.duration && !isNaN(audio.duration) && isFinite(audio.duration)) {
      percent = (audio.currentTime / audio.duration) * 100;
    }
    
    percent = Math.min(Math.max(percent, 0), 100);
    progress.value = percent;

    // Sécurité : Si percent vaut 0, on applique un fond uni visible au lieu d'un dégradé buggé
    if (percent === 0) {
        progress.style.background = '#55446e';
    } else {
        progress.style.background = `linear-gradient(to right, #f7ae56 0%, #f7ae56 ${percent}%, #55446e ${percent}%, #55446e 100%)`;
    }
    
    const total = (audio.duration && !isNaN(audio.duration)) ? formatTime(audio.duration) : "0:00";
    time.textContent = `${formatTime(audio.currentTime)} / ${total}`;

  }

  function formatTime(sec) {
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  }

  audio.addEventListener('loadedmetadata', updateProgressBar);
  audio.addEventListener('timeupdate', updateProgressBar);

  if (playPauseBtn) {
    playPauseBtn.onclick = function() {
      if (audio.paused) audio.play(); else audio.pause();
      updateButton();
    };
  }

  audio.addEventListener('play', updateButton);
  audio.addEventListener('pause', updateButton);

  // --- GLISSEMENT ET CLIC (SOURIS + TACTILE MOBILE) ---
  if (progress) {
    function scrub(e) {
      if (audio.duration && !isNaN(audio.duration)) {
        const rect = progress.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        
        if (clientX !== undefined) {
          const pos = (clientX - rect.left) / rect.width;
          const percent = Math.min(Math.max(pos * 100, 0), 100);
          
          progress.value = percent;
          audio.currentTime = pos * audio.duration;
          updateProgressBar();
        }
      }
    }

    progress.addEventListener('mousedown', function(e) {
      scrub(e);
      function onMouseMove(moveEvent) { scrub(moveEvent); }
      function onMouseUp() {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
      }
      document.addEventListener('mousemove', onMouseMove);
      document.addEventListener('mouseup', onMouseUp);
    });

    progress.addEventListener('touchstart', function(e) {
      scrub(e);
      function onTouchMove(moveEvent) { scrub(moveEvent); }
      function onTouchEnd() {
        document.removeEventListener('touchmove', onTouchMove);
        document.removeEventListener('touchend', onTouchEnd);
      }
      document.addEventListener('touchmove', onTouchMove, { passive: true });
      document.addEventListener('touchend', onTouchEnd);
    });
  }

  // --- GESTION AUDIO (VOLUME ET MUTE) ---
  if (muteBtn && volumeSlider) {
    function changeVolume(value) {
      audio.volume = value;
      audio.muted = (audio.volume === 0);
      muteBtn.classList.toggle('is-muted', audio.muted);
    }

    volumeSlider.addEventListener('input', (e) => changeVolume(e.target.value));
    volumeSlider.addEventListener('change', (e) => changeVolume(e.target.value));

    muteBtn.onclick = function() {
      if (audio.muted) {
        audio.muted = false;
        audio.volume = lastVolume > 0 ? lastVolume : 1;
        volumeSlider.value = audio.volume;
      } else {
        lastVolume = audio.volume;
        audio.muted = true;
        audio.volume = 0;
        volumeSlider.value = 0;
      }
      muteBtn.classList.toggle('is-muted', audio.muted);
    };
  }
  // --- GESTION DE LA FIN DE LA LECTURE AUDIO ---
  audio.addEventListener('ended', function() {
    audio.currentTime = 0; // Remet la piste à zéro au niveau du moteur
    audio.pause(); // Force l'état en pause
    
    // Remet la barre graphiquement au début (0%)
    if (progress) {
        progress.value = 0;
        progress.style.background = `linear-gradient(to right, #f7ae56 0%, #f7ae56 0%, #444 0%, #444 100%)`; // Votre couleur de fond violette
    }
    
    // Force la mise à jour visuelle du bouton Play et du compteur (0:00)
    updateButton();
    updateProgressBar();
  });
});