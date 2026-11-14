document.addEventListener('DOMContentLoaded', () => {
      const openBtns = document.querySelectorAll('.e_faire-un-don');
      const modal = document.getElementById('haWidgetModal');
      const closeBtn = document.getElementById('closeHaWidgetBtn');
      const body = document.body;

      closeBtn.innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <line x1="6" y1="6" x2="18" y2="18" stroke="#333" stroke-width="2" stroke-linecap="round" />
          <line x1="18" y1="6" x2="6" y2="18" stroke="#333" stroke-width="2" stroke-linecap="round" />
        </svg>`;

      openBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          modal.classList.add('open');
          modal.style.display = 'flex';
          body.style.overflow = 'hidden';
          body.style.overscrollBehaviorY = 'none';
        });
      });

      closeBtn.addEventListener('click', () => {
        modal.classList.remove('open');
        modal.style.display = 'none';
        body.style.overflow = '';
        body.style.overscrollBehaviorY = '';
      });

      closeBtn.addEventListener('mouseenter', () => closeBtn.style.background = '#E0E0E8');
      closeBtn.addEventListener('mouseleave', () => closeBtn.style.background = '#EFEFF4');
    })