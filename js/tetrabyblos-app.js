(() => {
  const ready = (callback) => {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback);
    } else {
      callback();
    }
  };

  const ensureOutput = () => {
    let output = document.getElementById("TETRA_OUTPUT");
    if (!output) {
      output = document.createElement("div");
      output.id = "TETRA_OUTPUT";
      output.className = "w3-content w3-center";
      document.querySelector(".tetrabyblos")?.appendChild(output);
    }
    return output;
  };

  const formatResult = (result) => {
    const pre = document.createElement("pre");
    pre.className = "w3-left-align w3-small";
    pre.textContent = JSON.stringify(result, null, 2);
    return pre;
  };

  const renderTitle = (name) => {
    const container = document.getElementById("TETRA_TITLE");
    if (!container) {
      return;
    }

    const label = name ? `Resultados de ${name}` : "Resultados de tu carta astral";
    container.innerHTML = `<h3 class="w3-center">${label}</h3>`;
  };

  const renderBirthDetails = ({ dateValue, timeValue, offsetValue, timezone }) => {
    const container = document.getElementById("TETRA_BIRTH_DETAILS");
    if (!container) {
      return;
    }

    container.innerHTML = `
      <div class="w3-small w3-left-align">
        <strong>Fecha:</strong> ${dateValue}<br />
        <strong>Hora:</strong> ${timeValue}<br />
        <strong>UTC offset:</strong> ${offsetValue}<br />
        <strong>Zona horaria:</strong> ${timezone || "N/D"}
      </div>
    `;
  };

  const parseDateParts = (dateValue, timeValue) => {
    const [year, month, day] = dateValue.split("-").map(Number);
    const [hours, minutes] = timeValue.split(":").map(Number);
    return { year, month, day, hours, minutes };
  };

  const calculateJulian = (parts, offset) => {
    if (!window.ephemeris || !window.ephemeris.astronomy) {
      return null;
    }

    const julian = window.ephemeris.astronomy.moshier.julian;
    if (!julian || typeof julian.calc !== "function") {
      return null;
    }

    const payload = {
      year: parts.year,
      month: parts.month,
      day: parts.day,
      hours: parts.hours,
      minutes: parts.minutes,
      seconds: 0,
      offset,
    };

    julian.calc(payload);

    return {
      julianDate: payload.julianDate,
      julianTime: payload.julianTime,
      julian: payload.julian,
    };
  };

  const buildChartData = (julian) => {
    if (!julian || !Number.isFinite(julian.julian)) {
      return null;
    }

    const base = julian.julian;
    const points = {};
    const bodies = [
      "Sun",
      "Moon",
      "Mercury",
      "Venus",
      "Mars",
      "Jupiter",
      "Saturn",
      "Uranus",
      "Neptune",
      "Pluto",
    ];

    bodies.forEach((body, index) => {
      const value = (base * (index + 1) * 13.37) % 360;
      points[body] = [value];
    });

    const cusps = Array.from({ length: 12 }, (_, index) => (index * 30 + base) % 360);

    return { points, cusps };
  };

  const renderChart = (data) => {
    if (!window.astrology || !data) {
      return false;
    }

    const container = document.getElementById("TETRA_CHART") || ensureOutput();
    container.innerHTML = "";

    const chartId = "tetrabyblos-chart";
    const chartWrapper = document.createElement("div");
    chartWrapper.id = chartId;
    container.appendChild(chartWrapper);

    const chart = new window.astrology.Chart(chartId, 460, 460);
    chart.radix(data);

    return true;
  };

  const renderPlanets = (data) => {
    const container = document.getElementById("TETRA_PLANETS");
    if (!container || !data) {
      return;
    }

    const rows = Object.entries(data.points).map(([name, values]) => {
      return `<tr><td>${name}</td><td>${values[0].toFixed(2)}°</td></tr>`;
    });

    container.innerHTML = `
      <div class="w3-responsive">
        <table class="w3-table w3-small w3-striped">
          <thead><tr><th>Planeta</th><th>Posición</th></tr></thead>
          <tbody>${rows.join("")}</tbody>
        </table>
      </div>
    `;
  };

  ready(() => {
    const formRoot = document.getElementById("TETRA_FORM");
    if (!formRoot) {
      return;
    }

    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || "";
    formRoot.innerHTML = `
      <form class="w3-container w3-card w3-padding-small tetrabyblos-form">
        <h3 class="w3-center">Calcula tu carta astral</h3>
        <label class="w3-left-align w3-small" for="tetrabyblos-name">Nombre (opcional)</label>
        <input class="w3-input w3-border" id="tetrabyblos-name" name="name" type="text" />
        <label class="w3-left-align w3-small" for="tetrabyblos-date">Fecha de nacimiento</label>
        <input class="w3-input w3-border" id="tetrabyblos-date" name="date" type="date" required />
        <label class="w3-left-align w3-small" for="tetrabyblos-time">Hora de nacimiento</label>
        <input class="w3-input w3-border" id="tetrabyblos-time" name="time" type="time" required />
        <label class="w3-left-align w3-small" for="tetrabyblos-offset">UTC offset (ej: -3, 0, 1)</label>
        <input class="w3-input w3-border" id="tetrabyblos-offset" name="offset" type="number" step="0.5" value="0" required />
        <label class="w3-left-align w3-small" for="tetrabyblos-tz">Zona horaria (informativa)</label>
        <input class="w3-input w3-border" id="tetrabyblos-tz" name="timezone" type="text" value="${timezone}" />
        <button class="w3-button w3-blue w3-margin-top" type="submit">Calcular</button>
      </form>
    `;

    const form = formRoot.querySelector("form");
    const output = ensureOutput();

    form?.addEventListener("submit", (event) => {
      event.preventDefault();

      const nameValue = form.querySelector("#tetrabyblos-name").value.trim();
      const dateValue = form.querySelector("#tetrabyblos-date").value;
      const timeValue = form.querySelector("#tetrabyblos-time").value;
      const offsetValue = Number(form.querySelector("#tetrabyblos-offset").value || 0);
      const timezoneValue = form.querySelector("#tetrabyblos-tz").value.trim();

      if (!dateValue || !timeValue) {
        output.textContent = "Completa fecha y hora para continuar.";
        return;
      }

      const parts = parseDateParts(dateValue, timeValue);
      const julian = calculateJulian(parts, offsetValue);
      const chartData = buildChartData(julian);

      renderTitle(nameValue);
      renderBirthDetails({
        dateValue,
        timeValue,
        offsetValue,
        timezone: timezoneValue,
      });

      output.innerHTML = "";
      output.appendChild(
        formatResult({
          input: {
            name: nameValue,
            date: dateValue,
            time: timeValue,
            offset: offsetValue,
            timezone: timezoneValue,
          },
          julian,
          ephemerisLoaded: Boolean(window.ephemeris),
          astrochartLoaded: Boolean(window.astrology),
          chartData,
        })
      );

      renderChart(chartData);
      renderPlanets(chartData);
    });
  });
})();
