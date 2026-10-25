(() => {
  "use strict";
  function draw() {
    if (!window.Chart) return;
    document.querySelectorAll("[data-chart]").forEach((canvas) => {
      if (canvas._chart) canvas._chart.destroy();
      let labels = [],
        datasets = [];
      try {
        labels = JSON.parse(canvas.dataset.labels || "[]");
        datasets = JSON.parse(canvas.dataset.datasets || "[]");
      } catch (e) {
        return;
      }
      const css = getComputedStyle(document.documentElement);
      const accent = css.getPropertyValue("--sp-accent").trim() || "#A3D65C";
      const text = css.getPropertyValue("--sp-muted").trim() || "#8FA597";
      const surface =
        css.getPropertyValue("--sp-surface-2").trim() || "#16241C";
      if (canvas.dataset.chart === "line") {
        canvas._chart = new Chart(canvas, {
          type: "line",
          data: {
            labels,
            datasets: datasets.map((d, i) => ({
              ...d,
              borderColor: i === 0 ? accent : text,
              backgroundColor: i === 0 ? accent + "20" : text + "20",
              borderDash: i === 1 ? [6, 5] : [],
              tension: 0.35,
              fill: false,
            })),
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { labels: { color: text } },
              tooltip: { mode: "index", intersect: false },
            },
            scales: {
              x: { ticks: { color: text }, grid: { color: surface } },
              y: {
                beginAtZero: true,
                ticks: { precision: 0, color: text },
                grid: { color: surface },
              },
            },
          },
        });
      } else if (canvas.dataset.chart === "doughnut") {
        canvas._chart = new Chart(canvas, {
          type: "doughnut",
          data: {
            labels,
            datasets: [
              {
                data: datasets[0]?.data || [],
                backgroundColor: [accent, "#FF8A7A"],
                borderWidth: 0,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "70%",
            plugins: { legend: { labels: { color: text } } },
          },
        });
      }
    });
  }
  document.addEventListener("DOMContentLoaded", draw);
  document.addEventListener("sipeka:themechange", () => setTimeout(draw, 30));
})();
