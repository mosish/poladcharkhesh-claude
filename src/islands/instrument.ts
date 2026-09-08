/**
 * Wires the visualiser panel: mode buttons, the speed slider, and the readout.
 *
 * The temperature cell only exists in thermal mode. Showing an estimated
 * temperature next to measured dimensions, in the same typographic treatment,
 * would imply the two carry equal weight — they do not.
 */

import { BearingRenderer, type BearingMode, type BearingPayload } from './bearing';

const MODES: BearingMode[] = ['assembly', 'cutaway', 'dimensions', 'thermal'];

function readPayload(canvas: HTMLCanvasElement): BearingPayload | null {
  const id = canvas.dataset.payload;
  if (!id) return null;
  const source = document.getElementById(id);
  if (!source) return null;
  try {
    return JSON.parse(source.textContent ?? '') as BearingPayload;
  } catch {
    return null;
  }
}

/**
 * Engineering values keep Latin digits in both languages. A bearing speed is a
 * figure an engineer copies into a calculation or a purchase order, and the
 * rest of the specification typography treats numbers the same way.
 */
function formatNumber(value: number): string {
  return value.toLocaleString('en-US');
}

/** Every canvas on the page gets a renderer; only the panel gets controls. */
export function initBearingVisuals(): BearingRenderer[] {
  const renderers: BearingRenderer[] = [];

  for (const canvas of document.querySelectorAll<HTMLCanvasElement>('[data-bearing-canvas]')) {
    const payload = readPayload(canvas);
    if (!payload) continue;

    try {
      const renderer = new BearingRenderer(canvas, payload, canvas.dataset.ambient === 'true');
      renderers.push(renderer);
      (canvas as HTMLCanvasElement & { __renderer?: BearingRenderer }).__renderer = renderer;
    } catch {
      // No 2D context. The noscript fallback and the specification table below
      // already carry the information.
    }
  }

  return renderers;
}

export function initInstrument(): void {
  const panel = document.querySelector<HTMLElement>('[data-instrument]');
  if (!panel) return;

  const canvas = panel.querySelector<HTMLCanvasElement>('[data-bearing-canvas]');
  const renderer = canvas
    ? (canvas as HTMLCanvasElement & { __renderer?: BearingRenderer }).__renderer
    : undefined;
  if (!canvas || !renderer) return;

  const payload = readPayload(canvas);
  const rpmUnit = payload?.labels.rpm ?? '';

  // The unit is written once, into the cell's label, rather than repeated on
  // every value where it would wrap the figure onto a second line.
  const speedUnitSlot = panel.querySelector<HTMLElement>('[data-instrument-speed-unit]');
  if (speedUnitSlot && rpmUnit !== '') {
    speedUnitSlot.textContent = ` (${rpmUnit})`;
  }

  const buttons = Array.from(panel.querySelectorAll<HTMLButtonElement>('[data-instrument-mode]'));
  const slider = panel.querySelector<HTMLInputElement>('[data-instrument-slider]');
  const speedCell = panel.querySelector<HTMLElement>('[data-instrument-speed]');
  const tempCell = panel.querySelector<HTMLElement>('[data-instrument-temp]');
  const tempWrap = panel.querySelector<HTMLElement>('[data-instrument-temp-cell]');
  const thermalNote = panel.querySelector<HTMLElement>('[data-instrument-thermal-note]');

  const updateReadout = (): void => {
    const speed = renderer.currentSpeed();
    if (speedCell) {
      speedCell.textContent = speed === null ? '—' : formatNumber(speed);
    }

    const estimate = renderer.estimatedTemperature();
    if (tempCell) {
      tempCell.textContent = estimate === null ? '—' : `≈ ${formatNumber(estimate.celsius)} °C`;
    }
  };

  const setMode = (mode: BearingMode): void => {
    renderer.setMode(mode);
    for (const button of buttons) {
      button.setAttribute(
        'aria-pressed',
        button.dataset.instrumentMode === mode ? 'true' : 'false',
      );
    }
    const thermal = mode === 'thermal';
    if (tempWrap) tempWrap.hidden = !thermal;
    if (thermalNote) thermalNote.hidden = !thermal;
    updateReadout();
  };

  for (const button of buttons) {
    button.addEventListener('click', () => {
      const mode = button.dataset.instrumentMode as BearingMode | undefined;
      if (mode && MODES.includes(mode)) setMode(mode);
    });
  }

  if (slider) {
    const apply = (): void => {
      renderer.setSpeedFraction(Number(slider.value) / 100);
      updateReadout();
    };
    slider.addEventListener('input', apply);
    renderer.setSpeedFraction(Number(slider.value) / 100);
  }

  updateReadout();
}
