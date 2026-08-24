document.addEventListener('DOMContentLoaded', function() {
  // --- 1. DÉCLARATION DES ÉLÉMENTS ---
  const video = document.getElementById('video');
  const wrapper = document.querySelector('.wrapper-video');
  const playPauseBtn = document.getElementById('playPauseBtn');
  const progress = document.getElementById('progress');
  const time = document.getElementById('time');
  const muteBtn = document.getElementById('muteBtn');
  const volumeSlider = document.getElementById('volumeSlider');
  const fullscreenBtn = document.getElementById('fullscreenBtn');
  
  let hideTimeout; 
  let lastVolume = 1; // Sauvegarde le volume précédent avant de muter

  if (!video) return;

  // --- 2. FONCTIONS DE MISE À JOUR VISUELLE ---

  function updateButton() {
    if (playPauseBtn) {
      if (video.paused) {
        playPauseBtn.classList.remove('is-playing');
      } else {
        playPauseBtn.classList.add('is-playing');
      }
    }
  }

  function updateProgressBar() {
    if (!progress || !time) return;

    let percent = 0;
    if (video.duration && !isNaN(video.duration) && isFinite(video.duration)) {
      percent = (video.currentTime / video.duration) * 100;
    }
    
    percent = Math.min(Math.max(percent, 0), 100);
    progress.value = percent;
    
    const total = (video.duration && !isNaN(video.duration)) ? formatTime(video.duration) : "0:00";
    time.textContent = `${formatTime(video.currentTime)} / ${total}`;
    
    progress.style.background = `linear-gradient(to right, #f7ae56 0%, #f7ae56 ${percent}%, #444 ${percent}%, #444 100%)`;
  }

  function formatTime(sec) {
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  }

  function showControls() {
    if (!wrapper) return;
    wrapper.classList.remove('hide-controls');
    clearTimeout(hideTimeout);

    if (!video.paused) {
      hideTimeout = setTimeout(() => {
        wrapper.classList.add('hide-controls');
      }, 2000);
    }
  }

  // --- 3. ÉVÉNEMENTS DU LECTEUR ---

  video.addEventListener('loadedmetadata', updateProgressBar);
  video.ontimeupdate = updateProgressBar;

  if (playPauseBtn) {
    playPauseBtn.onclick = function() {
      if (video.paused) {
        video.play();
      } else {
        video.pause();
      }
      updateButton();
    };
  }

  video.addEventListener('play', () => {
    updateButton();
    showControls();
  });
  video.addEventListener('pause', () => {
    clearTimeout(hideTimeout);
    if (wrapper) wrapper.classList.remove('hide-controls');
    updateButton();
  });

  // --- GESTION DU CLIC ET DÉPLACEMENT SOURIS + TACTILE (SEEKING EN CONTINU) ---
  if (progress) {
    function scrub(e) {
      if (video.duration && !isNaN(video.duration)) {
        const rect = progress.getBoundingClientRect();
        // Capture la position de la souris OU du premier doigt posé sur l'écran
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        
        if (clientX !== undefined) {
          const pos = (clientX - rect.left) / rect.width;
          const percent = Math.min(Math.max(pos * 100, 0), 100);
          
          progress.value = percent;
          video.currentTime = pos * video.duration;
          updateProgressBar();
        }
      }
    }

    // Comportement de drag à la souris
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

    // Comportement de drag au doigt (Tactile fluide sans scroll)
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

    // --- GESTION AUDIO (MUTE ET VOLUME SLIDER SYNCHRONISÉS) ---
  if (muteBtn && volumeSlider) {
    
    // Fonction unique pour appliquer le volume
    function changeVolume(value) {
      video.volume = value;
      video.muted = (video.volume === 0);
      muteBtn.classList.toggle('is-muted', video.muted);
    }

    // Capture le glissement en temps réel
    volumeSlider.addEventListener('input', function(e) {
      changeVolume(e.target.value);
    });

    // Capture le clic brut sur la barre
    volumeSlider.addEventListener('change', function(e) {
      changeVolume(e.target.value);
    });

    // Action du clic sur le bouton Mute
    muteBtn.onclick = function() {
      if (video.muted) {
        video.muted = false;
        video.volume = lastVolume > 0 ? lastVolume : 1;
        volumeSlider.value = video.volume;
      } else {
        lastVolume = video.volume;
        video.muted = true;
        video.volume = 0;
        volumeSlider.value = 0;
      }
      muteBtn.classList.toggle('is-muted', video.muted);
    };

    video.addEventListener('volumechange', function() {
      muteBtn.classList.toggle('is-muted', video.muted);
    });
  }

  // Clic sur le bouton Plein Écran
  if (fullscreenBtn && wrapper) {
    fullscreenBtn.onclick = function() {
      if (!document.fullscreenElement) {
        video.controls = false; 
        wrapper.requestFullscreen().catch(err => {
          console.error(`Erreur plein écran: ${err.message}`);
        });
      } else {
        document.exitFullscreen();
      }
    };

    document.addEventListener('fullscreenchange', function() {
      if (document.fullscreenElement === wrapper) {
        fullscreenBtn.classList.add('is-fullscreen');
        video.controls = false; 
      } else {
        fullscreenBtn.classList.remove('is-fullscreen');
      }
    });
  }

  // --- 4. MASQUAGE AUTOMATIQUE (MOUVEMENTS DE SOURIS) ---
  if (wrapper) {
    wrapper.addEventListener('mousemove', showControls);
    wrapper.addEventListener('mouseleave', () => {
      if (!video.paused) {
        wrapper.classList.add('hide-controls');
      }
    });
  }

  // --- 5. RACCOURCIS CLAVIER ---
  document.addEventListener('keydown', function(event) {
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
      return;
    }

    const keysToPrevent = ['Space', ' ', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
    if (keysToPrevent.includes(event.key) || keysToPrevent.includes(event.code)) {
      event.preventDefault();
    }

    switch (event.key.toLowerCase()) {
      case ' ':
      case 'k': 
        if (video.paused) video.play(); else video.pause();
        updateButton();
        showControls();
        break;

      case 'arrowleft': 
        video.currentTime = Math.max(0, video.currentTime - 5);
        updateProgressBar();
        showControls();
        break;

      case 'arrowright': 
        video.currentTime = Math.min(video.duration, video.currentTime + 5);
        updateProgressBar();
        showControls();
        break;

      case 'm': 
        if (muteBtn) muteBtn.click();
        showControls();
        break;

      case 'f': 
        if (fullscreenBtn) fullscreenBtn.click();
        break;
    }
  });
  
  // --- GESTION DE LA FIN DE LA VIDÉO ---
  video.addEventListener('ended', function() {
    video.currentTime = 0;
    video.pause(); 
    
    if (progress) {
      progress.value = 0;
      progress.style.background = `linear-gradient(to right, #f7ae56 0%, #f7ae56 0%, #444 0%, #444 100%)`;
    }
    
    updateButton();
    updateProgressBar(); 
  });
});
