document.addEventListener('DOMContentLoaded', () => {
  const forms = Array.from(document.querySelectorAll('form[action*="formspree.io"]'));

  forms.forEach(form => {
    const container = form.closest('.container') || document;
    const successEl = container.querySelector('[data-fs-success]');
    const errorEl = container.querySelector('[data-fs-error]');
    const submitBtn = form.querySelector('[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.textContent : '';

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (successEl) successEl.style.display = 'none';
      if (errorEl) errorEl.style.display = 'none';

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';
      }

      try {
        const formData = new FormData(form);

        const res = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: {
            'Accept': 'application/json'
          }
        });

        if (res.ok) {
          // Some Formspree endpoints return 200 with JSON
          let payload = {};
          try { payload = await res.json(); } catch (err) { payload = { ok: true }; }

          if (successEl) {
            successEl.style.display = 'block';
            if (payload && payload.message) successEl.textContent = payload.message;
          } else {
            alert('Mensaje enviado correctamente.');
          }
          form.reset();
        } else {
          let payload = {};
          try { payload = await res.json(); } catch (err) { payload = { error: 'Error al enviar el formulario.' }; }

          if (errorEl) {
            errorEl.style.display = 'block';
            if (payload && (payload.error || payload.message)) errorEl.textContent = payload.error || payload.message;
          } else {
            alert(payload.error || 'Error al enviar el formulario.');
          }
        }
      } catch (err) {
        if (errorEl) {
          errorEl.style.display = 'block';
        } else {
          alert('Error de red al enviar el formulario.');
        }
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalBtnText;
        }
      }
    });
  });
});
