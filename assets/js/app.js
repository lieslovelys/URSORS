document.documentElement.classList.add("js");
document.addEventListener("DOMContentLoaded", () => {
  const accountName = document.querySelector(".sidebar-account-details strong");
  if (accountName) {
    const fitAccountName = () => {
      accountName.style.fontSize = "12.8px";
      let size = 12.8;
      while (accountName.scrollWidth > accountName.clientWidth && size > 10) {
        size = Math.max(10, size - 0.2);
        accountName.style.fontSize = size + "px";
      }
    };
    new ResizeObserver(fitAccountName).observe(accountName.parentElement);
    document.fonts.ready.then(fitAccountName);
  }
  document.querySelectorAll("[data-photo-open]").forEach((button) => {
    button.addEventListener("click", () => {
      document.getElementById(button.dataset.photoOpen)?.showModal();
    });
  });
  document.querySelectorAll(".photo-editor").forEach((editor) => {
    editor.querySelector("[data-photo-cancel]").addEventListener("click", () => editor.close());
    editor.addEventListener("close", () => {
      editor.querySelector("form").reset();
      const input = editor.querySelector("input[type=file]");
      input.setCustomValidity("");
      const preview = editor.querySelector(".image-preview");
      if (preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
      delete preview.dataset.url;
      preview.removeAttribute("src");
      preview.hidden = true;
    });
  });
  const passwordIcon = (show) =>
    '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>' +
    (show ? '<path d="m3 3 18 18"/>' : '') + '</svg>';
  document.querySelectorAll("[data-password]").forEach((button) => {
    button.innerHTML = passwordIcon(false);
    button.setAttribute("aria-label", "Show password");
    button.addEventListener("click", () => {
      const input = document.getElementById(button.dataset.password);
      const show = input.type === "password";
      input.type = show ? "text" : "password";
      button.innerHTML = passwordIcon(show);
      button.setAttribute("aria-label", show ? "Hide password" : "Show password");
      button.setAttribute("aria-pressed", String(show));
    });
  });
  document.querySelectorAll("[data-preview]").forEach((input) =>
    input.addEventListener("change", () => {
      const preview = document.getElementById(input.dataset.preview);
      const file = input.files[0];
      if (preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
      preview.hidden = true;
      if (!file) return;
      if (file.size > 10 * 1024 * 1024) {
        input.setCustomValidity("Choose a file smaller than 10 MB.");
        input.reportValidity();
        return;
      }
      input.setCustomValidity("");
      if (!file.type.startsWith("image/")) return;
      preview.src = URL.createObjectURL(file);
      preview.dataset.url = preview.src;
      preview.hidden = false;
    }),
  );
  document.querySelectorAll("form[data-validate] input").forEach((input) => {
    const message = document.getElementById(input.id + "-error");
    const validate = () => {
      if (!message) return;
      let text = "";
      if (input.validity.valueMissing) text = "Please fill in this field.";
      else if (input.validity.typeMismatch)
        text = "Enter a complete email address, like name@example.com.";
      else if (input.validity.tooShort)
        text = "Use at least " + input.minLength + " characters.";
      message.textContent = text;
      input.setAttribute("aria-invalid", String(!!text));
    };
    input.addEventListener("blur", validate);
    input.addEventListener("input", () => {
      if (input.getAttribute("aria-invalid") === "true") validate();
    });
    input.addEventListener("invalid", validate);
  });
  const dialog = document.querySelector("#confirm-dialog");
  let pending = null;
  document.addEventListener("submit", (event) => {
      const form = event.target;
      if (!form.matches("form[data-confirm]")) return;
      if (form.dataset.confirmed === "yes") return;
      if (!dialog?.showModal) {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        return;
      }
      event.preventDefault();
      pending = { form, submitter: event.submitter };
      dialog.querySelector("#confirm-title").textContent =
        form.dataset.confirmTitle || "Please confirm";
      dialog.querySelector("[data-yes]").textContent =
        form.dataset.confirmButton || "Yes, continue";
      dialog.querySelector("[data-confirm-text]").textContent =
        form.dataset.confirm;
      dialog.showModal();
      dialog.querySelector("[data-cancel]").focus();
  });
  dialog?.querySelector("[data-cancel]")?.addEventListener("click", () => {
    dialog.close();
    pending = null;
  });
  dialog?.querySelector("[data-yes]")?.addEventListener("click", () => {
    if (!pending) return;
    const { form, submitter } = pending;
    form.dataset.confirmed = "yes";
    dialog.close();
    if (submitter) form.requestSubmit(submitter);
    else form.requestSubmit();
  });
  const feed = document.querySelector("[data-home-feed]");
  const next = feed?.querySelector("[data-feed-next]");
  if (next) {
    const posts = feed.querySelector("[data-feed-posts]");
    const status = feed.querySelector("[data-feed-status]");
    let loading = false;
    let observer;
    const loadMore = async () => {
      if (loading || next.hidden) return;
      loading = true;
      feed.setAttribute("aria-busy", "true");
      next.setAttribute("aria-disabled", "true");
      status.textContent = "Loading more posts…";
      try {
        const response = await fetch(next.href, { credentials: "same-origin" });
        if (!response.ok) throw new Error("Could not load posts");
        const doc = new DOMParser().parseFromString(await response.text(), "text/html");
        const incoming = doc.querySelector("[data-feed-posts]");
        if (!incoming) throw new Error("Please sign in again or refresh the page");
        for (const post of incoming.children) {
          if (!document.getElementById(post.id)) posts.append(document.importNode(post, true));
        }
        const following = doc.querySelector("[data-feed-next]");
        if (following) {
          next.href = following.getAttribute("href");
          status.textContent = "More posts loaded. Keep scrolling.";
          observer?.observe(next);
        } else {
          observer?.disconnect();
          if (document.activeElement === next) {
            status.tabIndex = -1;
            status.focus({ preventScroll: true });
          }
          next.hidden = true;
          status.textContent = "You have reached the end of the posts.";
        }
      } catch (error) {
        observer?.disconnect();
        status.textContent = "Could not load more posts. Use More posts to try again, or refresh the page.";
      } finally {
        loading = false;
        feed.removeAttribute("aria-busy");
        next.removeAttribute("aria-disabled");
      }
    };
    next.addEventListener("click", (event) => {
      // Modified clicks still open the ordinary paginated page.
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      loadMore();
    });
    if ("IntersectionObserver" in window) {
      observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) loadMore();
      }, { rootMargin: "200px" });
      observer.observe(next);
    }
  }
});
