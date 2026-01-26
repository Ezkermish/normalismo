(() => {
  "use strict";

  // Uppercase CURP in real time
  const curpInput = document.querySelector("[data-curp-input]");
  if (curpInput){
    curpInput.addEventListener("input", () => {
      const start = curpInput.selectionStart;
      const end = curpInput.selectionEnd;
      curpInput.value = (curpInput.value || "").toUpperCase().replace(/[^A-Z0-9]/g,"");
      curpInput.setSelectionRange(start,end);
    });
  }

  // Common helper: show bootstrap alert that auto-dismisses
  window.normalismoAlert = (containerId, message, type="danger", timeoutMs=5000) => {
    const host = document.getElementById(containerId);
    if (!host) return;
    host.innerHTML = `
      <div class="alert alert-${type} card-glass" role="alert">${escapeHtml(message)}</div>
    `;
    setTimeout(() => host.innerHTML = "", timeoutMs);
  };

  function escapeHtml(str){
    return String(str)
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;")
      .replaceAll('"',"&quot;")
      .replaceAll("'","&#039;");
  }
})();
