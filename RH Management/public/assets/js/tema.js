(() => {
  const STORAGE_KEY = "rh-management-theme";
  const HEX = /^#[0-9a-f]{6}$/i;

  const defaults = {
    primary: "#2f5bea",
    secondary: "#14b8a6",
    menu: "#0f172a",
    background: "#f6f8fb",
    surface: "#ffffff",
    card: "#162036",
  };

  const toRgb = (hex) => {
    const value = hex.replace("#", "");
    return {
      r: parseInt(value.slice(0, 2), 16),
      g: parseInt(value.slice(2, 4), 16),
      b: parseInt(value.slice(4, 6), 16),
    };
  };

  const toHex = ({ r, g, b }) =>
    `#${[r, g, b]
      .map((channel) => Math.max(0, Math.min(255, Math.round(channel))).toString(16).padStart(2, "0"))
      .join("")}`;

  const mix = (first, second, amount) => {
    const a = toRgb(first);
    const b = toRgb(second);
    return toHex({
      r: a.r + (b.r - a.r) * amount,
      g: a.g + (b.g - a.g) * amount,
      b: a.b + (b.b - a.b) * amount,
    });
  };

  const normalize = (value) => {
    const theme = { ...defaults, ...(value || {}) };
    return Object.fromEntries(
      Object.entries(defaults).map(([key, fallback]) => [
        key,
        HEX.test(theme[key]) ? theme[key].toLowerCase() : fallback,
      ])
    );
  };

  const getStoredTheme = () => {
    try {
      return normalize(JSON.parse(localStorage.getItem(STORAGE_KEY) || "null"));
    } catch {
      return { ...defaults };
    }
  };

  const apply = (value) => {
    const theme = normalize(value);
    const root = document.documentElement;
    const variables = {
      "--cor-primaria": theme.primary,
      "--cor-primaria-escura": mix(theme.primary, "#000000", 0.28),
      "--cor-primaria-clara": mix(theme.primary, "#ffffff", 0.88),
      "--cor-destaque": theme.secondary,
      "--cor-menu": theme.menu,
      "--cor-fundo": theme.background,
      "--cor-superficie": theme.surface,
      "--cor-card": theme.card,
      "--cor-borda-card": mix(theme.card, "#ffffff", 0.18),
    };

    Object.entries(variables).forEach(([name, color]) => root.style.setProperty(name, color));
    root.dataset.rhTheme = "personalizado";

    if (document.body) {
      document.body.style.backgroundColor = theme.menu;
    }
    root.style.backgroundColor = theme.menu;

    window.dispatchEvent(new CustomEvent("rh-theme-applied", { detail: theme }));
    return theme;
  };

  const save = (value) => {
    const theme = normalize(value);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(theme));
    apply(theme);

    const frame = document.getElementById("iframe");
    if (frame?.contentWindow) {
      frame.contentWindow.postMessage({ type: "rh-theme-apply", theme }, "*");
    }

    return theme;
  };

  const reset = () => {
    localStorage.removeItem(STORAGE_KEY);
    return save(defaults);
  };

  window.RHTheme = { defaults, get: getStoredTheme, apply, save, reset };
  apply(getStoredTheme());

  window.addEventListener("storage", (event) => {
    if (event.key === STORAGE_KEY) apply(getStoredTheme());
  });

  window.addEventListener("message", (event) => {
    if (event.data?.type === "rh-theme-apply") apply(event.data.theme);
  });
})();
