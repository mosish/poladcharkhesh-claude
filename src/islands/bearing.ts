/**
 * Bearing renderer.
 *
 * Four views of one bearing, all built from its catalogue dimensions:
 *
 *   assembly    front view, with the inner ring, cage and rolling elements
 *               turning at their true relative rates
 *   cutaway     half section through the axis, the drawing a catalogue uses
 *   dimensions  the same section with ISO dimension leaders on d, D, B and r
 *   thermal     estimated running temperature against speed
 *
 * Two honesty rules are built into this file:
 *
 *  1. Rolling element diameter and count are NOT published data. They are
 *     derived from d and D so the drawing is proportionate, and the interface
 *     never presents them as specification. Anything drawn from a derived
 *     value is drawn, never labelled with a number.
 *
 *  2. The thermal curve is an indicative model, not a measurement. It is
 *     rendered with its caveat visible, and it refuses to extrapolate past the
 *     published limiting speed — beyond that point the chart shows a warning
 *     band rather than a plausible-looking number.
 */

export interface BearingLabels {
  bore: string;
  od: string;
  width: string;
  speed: string;
  temp: string;
  mm: string;
  rpm: string;
}

export interface BearingPayload {
  code: string;
  name: string;
  family: string | null;
  element: 'ball' | 'roller' | 'none' | string;
  schematic: string | null;
  d: number | null;
  D: number | null;
  B: number | null;
  rMin: number | null;
  speedGreaseRpm: number | null;
  speedOilRpm: number | null;
  referenceSpeedRpm: number | null;
  sealed: boolean;
  labels: BearingLabels;
  rtl: boolean;
}

export type BearingMode = 'assembly' | 'cutaway' | 'dimensions' | 'thermal';

interface Geometry {
  /** Bore, outside diameter and width, in millimetres. */
  d: number;
  D: number;
  B: number;
  /** Pitch diameter of the rolling element set. */
  pitch: number;
  /** Rolling element diameter — derived for drawing, not published data. */
  element: number;
  /** Number of rolling elements laid out — derived for drawing. */
  count: number;
  /** Corner radius, published where available, otherwise a drawing default. */
  corner: number;
  roller: boolean;
}

const PALETTE = {
  bg: '#0d1233',
  grid: 'rgba(144, 202, 249, 0.09)',
  steelLight: '#e8edf6',
  steelMid: '#9fadc6',
  steelDark: '#5b6885',
  steelEdge: '#2c3654',
  cage: '#b8892f',
  cageDark: '#7c5b1c',
  seal: '#2b3247',
  accent: '#2196f3',
  accentSoft: 'rgba(33, 150, 243, 0.22)',
  dim: '#90caf9',
  dimText: '#cfe4fb',
  hatch: 'rgba(232, 237, 246, 0.34)',
  axis: 'rgba(144, 202, 249, 0.55)',
  warn: '#f0a137',
  danger: '#e35d4f',
  ok: '#4fd0a0',
} as const;

const prefersReducedMotion = (): boolean =>
  typeof window.matchMedia === 'function' &&
  window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Derive the drawing geometry.
 *
 * The element diameter factor is a drawing convention, not a specification:
 * roller bearings carry proportionally larger elements than ball bearings, so
 * the section reads correctly for each family at a glance.
 */
function deriveGeometry(payload: BearingPayload): Geometry | null {
  const { d, D, B } = payload;
  if (d === null || D === null || B === null || D <= d || B <= 0) {
    return null;
  }

  const roller = payload.element === 'roller';
  const section = (D - d) / 2;

  // 0.55 of the radial section is close to the real element diameter across the
  // catalogue — a 6204 lands at 7.4 mm against a true 7.94, a 22212 at 13.8
  // against roughly 15 — which is near enough for a drawing and never
  // presented as a specification.
  const element = section * 0.55;
  const pitch = (d + D) / 2;

  // Lay out as many elements as fit around the pitch circle with a realistic
  // working gap, then clamp to a range that stays legible on screen.
  const spacing = Math.asin(Math.min(0.98, (element * 1.6) / pitch)) * 2;
  const count = Math.max(6, Math.min(22, Math.floor((Math.PI * 2) / spacing)));

  return {
    d,
    D,
    B,
    pitch,
    element,
    count,
    corner: payload.rMin ?? Math.max(0.3, section * 0.08),
    roller,
  };
}

export class BearingRenderer {
  private readonly ctx: CanvasRenderingContext2D;
  private readonly geometry: Geometry | null;

  private mode: BearingMode = 'assembly';
  private speedFraction = 0.35;
  private innerAngle = 0;
  private cageAngle = 0;
  private elementAngle = 0;

  private frame = 0;
  private lastTime = 0;
  private running = false;
  private visible = true;
  private width = 0;
  private height = 0;

  private readonly resizeObserver?: ResizeObserver;
  private readonly intersectionObserver?: IntersectionObserver;

  constructor(
    private readonly canvas: HTMLCanvasElement,
    private readonly payload: BearingPayload,
    private readonly ambient = false,
  ) {
    const ctx = canvas.getContext('2d');
    if (!ctx) {
      throw new Error('Canvas 2D context unavailable');
    }
    this.ctx = ctx;
    this.geometry = deriveGeometry(payload);

    // The hero copy turns at a steady, unhurried rate: it is atmosphere, and a
    // fast-spinning bearing in the corner of the eye is distracting rather than
    // impressive.
    if (this.ambient) {
      this.speedFraction = 0.22;
    }

    this.resize();

    if (typeof ResizeObserver === 'function') {
      this.resizeObserver = new ResizeObserver(() => {
        this.resize();
        this.draw();
      });
      this.resizeObserver.observe(canvas);
    }

    // Animation stops entirely when the canvas is off screen. A rotating
    // bearing nobody is looking at is pure battery drain.
    if (typeof IntersectionObserver === 'function') {
      this.intersectionObserver = new IntersectionObserver(
        (entries) => {
          this.visible = entries.some((entry) => entry.isIntersecting);
          this.visible ? this.start() : this.stop();
        },
        { threshold: 0.05 },
      );
      this.intersectionObserver.observe(canvas);
    }

    this.start();
  }

  // ---------------------------------------------------------------- lifecycle

  setMode(mode: BearingMode): void {
    this.mode = mode;
    this.draw();
    this.shouldAnimate() ? this.start() : this.stop();
  }

  setSpeedFraction(fraction: number): void {
    if (this.ambient) {
      return;
    }
    this.speedFraction = Math.min(1.15, Math.max(0, fraction));
    if (!this.shouldAnimate()) {
      this.draw();
    }
  }

  /** The speed the readout displays, in r/min. */
  currentSpeed(): number | null {
    const limit = this.limitingSpeed();
    return limit === null ? null : Math.round((limit * this.speedFraction) / 10) * 10;
  }

  /**
   * Estimated running temperature.
   *
   * An indicative model: temperature rises from ambient with speed, steeply
   * near the limiting speed. It is deliberately not extrapolated past that
   * limit — a number there would imply the bearing can be run there.
   */
  estimatedTemperature(): { celsius: number; band: 'low' | 'normal' | 'elevated' | 'limit' | 'over' } | null {
    const limit = this.limitingSpeed();
    if (limit === null) {
      return null;
    }
    const ratio = this.speedFraction;
    const ambientC = 25;
    const rise = 48 * Math.pow(Math.min(ratio, 1), 1.6);
    const celsius = Math.round(ambientC + rise);

    let band: 'low' | 'normal' | 'elevated' | 'limit' | 'over' = 'normal';
    if (ratio > 1) band = 'over';
    else if (ratio > 0.85) band = 'limit';
    else if (ratio > 0.6) band = 'elevated';
    else if (ratio < 0.2) band = 'low';

    return { celsius, band };
  }

  limitingSpeed(): number | null {
    return (
      this.payload.speedGreaseRpm ??
      this.payload.referenceSpeedRpm ??
      this.payload.speedOilRpm ??
      null
    );
  }

  destroy(): void {
    this.stop();
    this.resizeObserver?.disconnect();
    this.intersectionObserver?.disconnect();
  }

  private shouldAnimate(): boolean {
    return this.mode === 'assembly' && !prefersReducedMotion() && this.visible;
  }

  private start(): void {
    if (this.running || !this.shouldAnimate()) {
      this.draw();
      return;
    }
    this.running = true;
    this.lastTime = performance.now();
    const tick = (now: number): void => {
      if (!this.running) return;
      const delta = Math.min(64, now - this.lastTime) / 1000;
      this.lastTime = now;
      this.advance(delta);
      this.draw();
      this.frame = requestAnimationFrame(tick);
    };
    this.frame = requestAnimationFrame(tick);
  }

  private stop(): void {
    this.running = false;
    if (this.frame) {
      cancelAnimationFrame(this.frame);
      this.frame = 0;
    }
  }

  private resize(): void {
    const rect = this.canvas.getBoundingClientRect();
    const dpr = Math.min(2.5, window.devicePixelRatio || 1);
    this.width = Math.max(1, Math.round(rect.width));
    this.height = Math.max(1, Math.round(rect.height));
    this.canvas.width = Math.round(this.width * dpr);
    this.canvas.height = Math.round(this.height * dpr);
    this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  /**
   * Advance the rotation.
   *
   * The relative rates are the real kinematics of a rolling bearing with a
   * stationary outer ring: the cage turns at roughly half shaft speed, reduced
   * by the element-to-pitch ratio, and each element spins much faster than the
   * shaft. Drawing them at arbitrary speeds would look plausible and be wrong.
   */
  private advance(delta: number): void {
    const geo = this.geometry;
    if (!geo) return;

    // A visually readable shaft rate, not real r/min — the readout carries the
    // actual figure.
    const shaftRate = (0.5 + this.speedFraction * 5.5) * Math.PI * 0.4;
    const ratio = geo.element / geo.pitch;

    const cageRate = (shaftRate / 2) * (1 - ratio);
    const elementRate = (shaftRate / 2) * (geo.pitch / geo.element) * (1 - ratio * ratio);

    this.innerAngle += shaftRate * delta;
    this.cageAngle += cageRate * delta;
    this.elementAngle += elementRate * delta;
  }

  // ------------------------------------------------------------------ drawing

  private draw(): void {
    const { ctx } = this;
    ctx.clearRect(0, 0, this.width, this.height);

    // Canvas text inherits the document direction, which would reorder a
    // dimension label like "⌀d 60" on the Persian site. Engineering notation is
    // left-to-right in both languages.
    ctx.direction = 'ltr';

    if (!this.geometry) {
      this.drawUnavailable();
      return;
    }

    switch (this.mode) {
      case 'assembly':
        this.drawAssembly();
        break;
      case 'cutaway':
        this.drawSection(false);
        break;
      case 'dimensions':
        this.drawSection(true);
        break;
      case 'thermal':
        this.drawThermal();
        break;
    }
  }

  private drawUnavailable(): void {
    const { ctx } = this;
    ctx.save();
    ctx.fillStyle = PALETTE.steelDark;
    ctx.font = '13px system-ui, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('—', this.width / 2, this.height / 2);
    ctx.restore();
  }

  /** ---------------------------------------------------------- front view */
  private drawAssembly(): void {
    const geo = this.geometry!;
    const { ctx } = this;
    const cx = this.width / 2;
    const cy = this.height / 2;
    const scale = (Math.min(this.width, this.height) * 0.82) / geo.D;

    const rOuter = (geo.D / 2) * scale;
    const rBore = (geo.d / 2) * scale;
    const rPitch = (geo.pitch / 2) * scale;
    const rElement = (geo.element / 2) * scale;
    const rOuterRace = rPitch + rElement;
    const rInnerRace = rPitch - rElement;

    ctx.save();
    ctx.translate(cx, cy);

    // The raceway cavity. Without it the rolling elements appear to float on
    // the page background instead of sitting inside the bearing.
    ctx.beginPath();
    ctx.arc(0, 0, rOuterRace + 1, 0, Math.PI * 2);
    ctx.arc(0, 0, Math.max(0, rInnerRace - 1), 0, Math.PI * 2, true);
    ctx.fillStyle = 'rgba(6, 9, 26, 0.75)';
    ctx.fill('evenodd');

    // Outer ring: a ring face lit from the upper left, so it reads as turned
    // steel rather than a flat disc.
    const outerGrad = ctx.createLinearGradient(-rOuter, -rOuter, rOuter, rOuter);
    outerGrad.addColorStop(0, PALETTE.steelLight);
    outerGrad.addColorStop(0.35, PALETTE.steelMid);
    outerGrad.addColorStop(0.7, PALETTE.steelDark);
    outerGrad.addColorStop(1, PALETTE.steelMid);

    ctx.beginPath();
    ctx.arc(0, 0, rOuter, 0, Math.PI * 2);
    ctx.arc(0, 0, rOuterRace, 0, Math.PI * 2, true);
    ctx.fillStyle = outerGrad;
    ctx.fill('evenodd');

    ctx.strokeStyle = PALETTE.steelEdge;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(0, 0, rOuter, 0, Math.PI * 2);
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(0, 0, rOuterRace, 0, Math.PI * 2);
    ctx.stroke();

    // Inner ring, rotating.
    ctx.save();
    ctx.rotate(this.innerAngle);
    const innerGrad = ctx.createLinearGradient(-rInnerRace, -rInnerRace, rInnerRace, rInnerRace);
    innerGrad.addColorStop(0, PALETTE.steelLight);
    innerGrad.addColorStop(0.4, PALETTE.steelMid);
    innerGrad.addColorStop(1, PALETTE.steelDark);

    ctx.beginPath();
    ctx.arc(0, 0, rInnerRace, 0, Math.PI * 2);
    ctx.arc(0, 0, rBore, 0, Math.PI * 2, true);
    ctx.fillStyle = innerGrad;
    ctx.fill('evenodd');

    ctx.strokeStyle = PALETTE.steelEdge;
    ctx.beginPath();
    ctx.arc(0, 0, rInnerRace, 0, Math.PI * 2);
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(0, 0, rBore, 0, Math.PI * 2);
    ctx.stroke();

    // A keyway-free shaft mark, so the inner ring's rotation is visible.
    ctx.strokeStyle = 'rgba(44, 54, 84, 0.55)';
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.moveTo(rBore, 0);
    ctx.lineTo(rInnerRace, 0);
    ctx.stroke();
    ctx.restore();

    // Cage: a band of brass-toned arcs between the elements.
    ctx.save();
    ctx.rotate(this.cageAngle);
    // A continuous ribbon broken by the element pockets, which is how a
    // pressed cage actually looks from the face.
    ctx.strokeStyle = PALETTE.cage;
    ctx.lineWidth = Math.max(2, rElement * 0.5);
    ctx.globalAlpha = 0.72;
    const step = (Math.PI * 2) / geo.count;
    const pocket = Math.asin(Math.min(0.98, rElement / rPitch)) * 1.12;
    for (let i = 0; i < geo.count; i += 1) {
      const from = i * step + pocket;
      const to = (i + 1) * step - pocket;
      if (to <= from) continue;
      ctx.beginPath();
      ctx.arc(0, 0, rPitch, from, to);
      ctx.stroke();
    }
    ctx.globalAlpha = 1;
    ctx.restore();

    // Rolling elements, carried round by the cage.
    ctx.save();
    ctx.rotate(this.cageAngle);
    for (let i = 0; i < geo.count; i += 1) {
      const angle = i * step;
      const x = Math.cos(angle) * rPitch;
      const y = Math.sin(angle) * rPitch;

      const ballGrad = ctx.createRadialGradient(
        x - rElement * 0.35,
        y - rElement * 0.35,
        rElement * 0.1,
        x,
        y,
        rElement,
      );
      ballGrad.addColorStop(0, '#ffffff');
      ballGrad.addColorStop(0.35, PALETTE.steelLight);
      ballGrad.addColorStop(1, PALETTE.steelDark);

      ctx.beginPath();
      if (geo.roller) {
        // Rollers are drawn as capsules aligned with the raceway.
        const half = rElement;
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(angle + Math.PI / 2);
        this.roundedRect(ctx, -half * 0.62, -half, half * 1.24, half * 2, half * 0.34);
        ctx.fillStyle = ballGrad;
        ctx.fill();
        ctx.strokeStyle = PALETTE.steelEdge;
        ctx.lineWidth = 0.8;
        ctx.stroke();
        ctx.restore();
      } else {
        ctx.arc(x, y, rElement, 0, Math.PI * 2);
        ctx.fillStyle = ballGrad;
        ctx.fill();
        ctx.strokeStyle = PALETTE.steelEdge;
        ctx.lineWidth = 0.8;
        ctx.stroke();

        // Spin mark, so element rotation is visible as well as orbit.
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(this.elementAngle);
        ctx.strokeStyle = 'rgba(44, 54, 84, 0.42)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(-rElement * 0.55, 0);
        ctx.lineTo(rElement * 0.55, 0);
        ctx.stroke();
        ctx.restore();
      }
    }
    ctx.restore();

    // A single specular sweep across the outer ring.
    ctx.save();
    ctx.beginPath();
    ctx.arc(0, 0, rOuter, 0, Math.PI * 2);
    ctx.arc(0, 0, rOuterRace, 0, Math.PI * 2, true);
    ctx.clip('evenodd');
    const sheen = ctx.createLinearGradient(-rOuter, -rOuter, 0, rOuter * 0.2);
    sheen.addColorStop(0, 'rgba(255,255,255,0.5)');
    sheen.addColorStop(0.45, 'rgba(255,255,255,0)');
    ctx.fillStyle = sheen;
    ctx.fillRect(-rOuter, -rOuter, rOuter * 2, rOuter * 2);
    ctx.restore();

    ctx.restore();
  }

  private roundedRect(
    ctx: CanvasRenderingContext2D,
    x: number,
    y: number,
    w: number,
    h: number,
    r: number,
  ): void {
    const radius = Math.min(r, w / 2, h / 2);
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.arcTo(x + w, y, x + w, y + h, radius);
    ctx.arcTo(x + w, y + h, x, y + h, radius);
    ctx.arcTo(x, y + h, x, y, radius);
    ctx.arcTo(x, y, x + w, y, radius);
    ctx.closePath();
  }

  /**
   * Canvas text does not fall back gracefully across scripts: a monospace
   * stack has no Persian glyphs, so Persian rendered in it arrives unshaped
   * and unjoined. Latin engineering notation keeps the mono face; Persian
   * labels get the interface face.
   */
  private static labelFont(text: string, size = 11): string {
    return /[؀-ۿ]/.test(text)
      ? `${size + 2}px "IRANSans", system-ui, sans-serif`
      : `${size}px ui-monospace, "JetBrains Mono", monospace`;
  }

  /** ------------------------------------------------------- half section */
  private drawSection(withDimensions: boolean): void {
    const geo = this.geometry!;
    const { ctx } = this;

    const rOuter = geo.D / 2;
    const rBore = geo.d / 2;
    const rPitch = geo.pitch / 2;
    const rElement = geo.element / 2;

    // A ball raceway is a groove with retaining shoulders; a roller raceway is
    // a plain land. Which one applies is a property of the family.
    const grooved = !geo.roller;

    // Spherical roller bearings in the 222xx and 223xx series are double row,
    // and drawing them with one roller would misrepresent the part.
    const rows = this.payload.family === 'spherical-roller' ? 2 : 1;
    const drawElement = rElement * (rows === 2 ? 0.78 : 1);
    const rowCentres = rows === 2 ? [geo.B * 0.27, geo.B * 0.73] : [geo.B * 0.5];

    // The raceway is set from the element actually drawn, so ring and rolling
    // element meet rather than leaving a gap the real part does not have.
    const shoulder = drawElement * (grooved ? 0.64 : 1.04);
    const grooveRadius = drawElement * 1.03;
    const raceOuter = rPitch + shoulder;
    const raceInner = rPitch - shoulder;
    const theta = Math.asin(Math.min(0.99, shoulder / grooveRadius));

    // Between the axis and the bore a half section shows nothing. Rather than
    // spend a third of the frame on emptiness, that run is broken out — the
    // convention catalogues use for a large-bore part.
    const sectionHeight = rOuter - rBore;
    const axisGap = sectionHeight * 0.28;
    const totalHeight = sectionHeight + axisGap;

    const marginX = withDimensions ? 88 : 104;
    const marginY = withDimensions ? 54 : 40;
    const usableW = Math.max(60, this.width - marginX * 2);
    const usableH = Math.max(60, this.height - marginY * 2);
    const scale = Math.min(usableW / geo.B, usableH / totalHeight) * 0.94;

    const drawnW = geo.B * scale;
    const drawnH = totalHeight * scale;
    const left = (this.width - drawnW) / 2;
    const axisY = (this.height + drawnH) / 2;

    // Radii are measured from the axis, but the hidden run is folded out of
    // the mapping so the section fills the frame.
    const hidden = rBore - axisGap;
    const X = (mm: number): number => left + mm * scale;
    const Y = (mm: number): number => axisY - (mm - hidden) * scale;

    // --- rotation axis: a chain line, as on a drawing ---------------------
    ctx.save();
    ctx.strokeStyle = PALETTE.axis;
    ctx.lineWidth = 1;
    ctx.setLineDash([16, 5, 3, 5]);
    ctx.beginPath();
    ctx.moveTo(X(-geo.B * 0.22), axisY);
    ctx.lineTo(X(geo.B * 1.22), axisY);
    ctx.stroke();
    ctx.setLineDash([]);

    // The break symbol marking the omitted run between axis and bore.
    const breakY = (axisY + Y(rBore)) / 2;
    ctx.strokeStyle = 'rgba(144, 202, 249, 0.4)';
    ctx.beginPath();
    const zig = Math.max(5, drawnW * 0.05);
    ctx.moveTo(X(0) - 6, breakY);
    for (let x = X(0) - 6, flip = 1; x < X(geo.B) + 6; x += zig, flip *= -1) {
      ctx.lineTo(x + zig, breakY + flip * 4);
    }
    ctx.stroke();
    ctx.restore();

    // The groove circle, shared by both rings.
    const gx = X(geo.B / 2);
    const gy = Y(rPitch);
    const gr = grooveRadius * scale;

    // --- outer ring -------------------------------------------------------
    const outerPath = new Path2D();
    outerPath.moveTo(X(0), Y(rOuter));
    outerPath.lineTo(X(geo.B), Y(rOuter));
    outerPath.lineTo(X(geo.B), Y(raceOuter));
    if (grooved) {
      // Canvas y grows downward, so the groove sits at negative angles;
      // sweeping anticlockwise passes over its deepest point.
      outerPath.arc(gx, gy, gr, -theta, -(Math.PI - theta), true);
    }
    outerPath.lineTo(X(0), Y(raceOuter));
    outerPath.closePath();

    // --- inner ring -------------------------------------------------------
    const innerPath = new Path2D();
    innerPath.moveTo(X(0), Y(rBore));
    innerPath.lineTo(X(geo.B), Y(rBore));
    innerPath.lineTo(X(geo.B), Y(raceInner));
    if (grooved) {
      innerPath.arc(gx, gy, gr, theta, Math.PI - theta, false);
    }
    innerPath.lineTo(X(0), Y(raceInner));
    innerPath.closePath();

    for (const path of [outerPath, innerPath]) {
      ctx.save();
      ctx.fillStyle = 'rgba(159, 173, 198, 0.16)';
      ctx.fill(path);
      // Section hatching at 45°, the convention for a cut face.
      ctx.clip(path);
      ctx.strokeStyle = PALETTE.hatch;
      ctx.lineWidth = 0.8;
      const span = this.width + this.height;
      for (let i = -span; i < span; i += 7) {
        ctx.beginPath();
        ctx.moveTo(i, 0);
        ctx.lineTo(i + this.height, this.height);
        ctx.stroke();
      }
      ctx.restore();

      ctx.save();
      ctx.strokeStyle = PALETTE.steelLight;
      ctx.lineWidth = 1.4;
      ctx.stroke(path);
      ctx.restore();
    }

    const er = drawElement * scale;

    // --- cage -------------------------------------------------------------
    // Drawn before the rolling elements, so only the slivers either side of
    // each element show — which is all a section through a pocket reveals.
    ctx.save();
    ctx.strokeStyle = PALETTE.cage;
    ctx.lineWidth = Math.max(1.2, er * 0.13);
    ctx.globalAlpha = 0.7;
    for (const offset of [drawElement * 0.92, -drawElement * 0.92]) {
      ctx.beginPath();
      ctx.moveTo(X(geo.B * 0.08), Y(rPitch + offset));
      ctx.lineTo(X(geo.B * 0.92), Y(rPitch + offset));
      ctx.stroke();
    }
    ctx.restore();

    // --- rolling elements -------------------------------------------------
    for (const centre of rowCentres) {
      const ex = X(centre);
      const ey = Y(rPitch);

      ctx.save();
      const grad = ctx.createRadialGradient(ex - er * 0.35, ey - er * 0.4, er * 0.1, ex, ey, er * 1.2);
      grad.addColorStop(0, '#ffffff');
      grad.addColorStop(0.42, PALETTE.steelLight);
      grad.addColorStop(1, PALETTE.steelMid);

      ctx.beginPath();
      if (geo.roller) {
        // A roller is longer along the axis than it is deep radially, and a
        // spherical roller is barrelled, which the corner radius stands in for.
        const halfW = er * 1.08;
        this.roundedRect(ctx, ex - halfW, ey - er, halfW * 2, er * 2, er * 0.62);
      } else {
        ctx.arc(ex, ey, er, 0, Math.PI * 2);
      }
      ctx.fillStyle = grad;
      ctx.fill();
      ctx.strokeStyle = PALETTE.steelEdge;
      ctx.lineWidth = 1.2;
      ctx.stroke();
      ctx.restore();
    }

    // --- seals, where the designation says the bearing has them -----------
    if (this.payload.sealed) {
      ctx.save();
      ctx.strokeStyle = PALETTE.seal;
      ctx.lineWidth = Math.max(3, drawnW * 0.055);
      ctx.lineCap = 'round';
      for (const face of [0.07, 0.93]) {
        ctx.beginPath();
        ctx.moveTo(X(geo.B * face), Y(raceOuter));
        ctx.lineTo(X(geo.B * face), Y(raceInner));
        ctx.stroke();
      }
      ctx.restore();
    }

    if (!withDimensions) {
      this.drawSectionLabels(X, Y, geo, {
        left,
        drawnW,
        rOuter,
        rBore,
        rPitch,
        raceOuter,
        raceInner,
        elementX: rowCentres[rowCentres.length - 1],
        cageOffset: drawElement * 0.92,
      });
      return;
    }

    this.drawDimensionOverlay(X, Y, geo, axisY, left, drawnW, rOuter, rBore);
  }

  /**
   * Part callouts.
   *
   * Labels sit in columns clear of the drawing, with elbow leaders back to the
   * feature they name. Putting them over the section would obscure exactly the
   * geometry they are pointing at.
   */
  private drawSectionLabels(
    X: (mm: number) => number,
    Y: (mm: number) => number,
    geo: Geometry,
    layout: {
      left: number;
      drawnW: number;
      rOuter: number;
      rBore: number;
      rPitch: number;
      raceOuter: number;
      raceInner: number;
      elementX: number;
      cageOffset: number;
    },
  ): void {
    const { ctx } = this;
    const rtl = this.payload.rtl;
    const words = rtl
      ? {
          outer: 'رینگ بیرونی',
          race: 'مسیر غلتش',
          element: geo.roller ? 'غلتک' : 'ساچمه',
          cage: 'قفسه',
          inner: 'رینگ داخلی',
        }
      : {
          outer: 'Outer ring',
          race: 'Raceway',
          element: geo.roller ? 'Roller' : 'Ball',
          cage: 'Cage',
          inner: 'Inner ring',
        };

    // side 1 = the column to the right of the drawing, -1 = to the left.
    const callouts: Array<{ text: string; x: number; y: number; side: 1 | -1 }> = [
      { text: words.outer, x: geo.B * 0.8, y: (layout.rOuter + layout.raceOuter) / 2, side: 1 },
      { text: words.race, x: geo.B * 0.06, y: layout.raceOuter, side: -1 },
      { text: words.element, x: layout.elementX, y: layout.rPitch, side: 1 },
      { text: words.cage, x: geo.B * 0.1, y: layout.rPitch - layout.cageOffset, side: -1 },
      { text: words.inner, x: geo.B * 0.22, y: (layout.rBore + layout.raceInner) / 2, side: -1 },
    ];

    const columnRight = layout.left + layout.drawnW + 22;
    const columnLeft = layout.left - 22;

    ctx.save();
    ctx.textBaseline = 'middle';

    // Spread each column's labels vertically so leaders never cross.
    for (const side of [1, -1] as const) {
      const group = callouts.filter((c) => c.side === side);
      group.sort((a, b) => b.y - a.y);

      group.forEach((callout, index) => {
        const anchorX = X(callout.x);
        const anchorY = Y(callout.y);
        const columnX = side === 1 ? columnRight : columnLeft;
        const labelY = Y(layout.rOuter) + 14 + index * ((Y(layout.rBore) - Y(layout.rOuter)) / Math.max(1, group.length));

        ctx.strokeStyle = 'rgba(144, 202, 249, 0.55)';
        ctx.lineWidth = 0.9;
        ctx.beginPath();
        ctx.moveTo(anchorX, anchorY);
        ctx.lineTo(columnX - side * 10, labelY);
        ctx.lineTo(columnX, labelY);
        ctx.stroke();

        ctx.beginPath();
        ctx.arc(anchorX, anchorY, 2, 0, Math.PI * 2);
        ctx.fillStyle = PALETTE.dim;
        ctx.fill();

        ctx.font = BearingRenderer.labelFont(callout.text);
        ctx.fillStyle = PALETTE.dimText;
        ctx.textAlign = side === 1 ? 'left' : 'right';
        ctx.fillText(callout.text, columnX + side * 6, labelY);
      });
    }

    ctx.restore();
  }
  private drawDimensionOverlay(
    X: (mm: number) => number,
    Y: (mm: number) => number,
    geo: Geometry,
    axisY: number,
    left: number,
    drawnW: number,
    rOuter: number,
    rBore: number,
  ): void {
    const { ctx } = this;
    ctx.save();
    ctx.strokeStyle = PALETTE.dim;
    ctx.fillStyle = PALETTE.dimText;
    ctx.lineWidth = 0.9;
    ctx.font = '11px ui-monospace, monospace';
    ctx.textBaseline = 'middle';
    ctx.textAlign = 'center';

    const arrow = (x: number, y: number, dx: number, dy: number): void => {
      const len = 6;
      const angle = Math.atan2(dy, dx);
      ctx.beginPath();
      ctx.moveTo(x, y);
      ctx.lineTo(x - len * Math.cos(angle - 0.35), y - len * Math.sin(angle - 0.35));
      ctx.lineTo(x - len * Math.cos(angle + 0.35), y - len * Math.sin(angle + 0.35));
      ctx.closePath();
      ctx.fillStyle = PALETTE.dim;
      ctx.fill();
    };

    const format = (value: number): string =>
      `${Number.isInteger(value) ? value : value.toFixed(1)}`;

    // --- B, below the section --------------------------------------------
    const bY = axisY + 30;
    ctx.beginPath();
    ctx.moveTo(X(0), Y(rBore));
    ctx.lineTo(X(0), bY + 8);
    ctx.moveTo(X(geo.B), Y(rBore));
    ctx.lineTo(X(geo.B), bY + 8);
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(X(0), bY);
    ctx.lineTo(X(geo.B), bY);
    ctx.stroke();
    arrow(X(0), bY, -1, 0);
    arrow(X(geo.B), bY, 1, 0);

    ctx.fillStyle = PALETTE.bg;
    const bLabel = `B ${format(geo.B)}`;
    const bWidth = ctx.measureText(bLabel).width + 10;
    ctx.fillRect(X(geo.B / 2) - bWidth / 2, bY - 8, bWidth, 16);
    ctx.fillStyle = PALETTE.dimText;
    ctx.fillText(bLabel, X(geo.B / 2), bY);

    // --- d and D, to the side --------------------------------------------
    const dimX = left + drawnW + 34;
    const dimX2 = left + drawnW + 62;

    ctx.beginPath();
    ctx.moveTo(X(geo.B), Y(rBore));
    ctx.lineTo(dimX + 8, Y(rBore));
    ctx.moveTo(X(geo.B), Y(rOuter));
    ctx.lineTo(dimX2 + 8, Y(rOuter));
    ctx.moveTo(X(geo.B), axisY);
    ctx.lineTo(dimX2 + 8, axisY);
    ctx.stroke();

    for (const [x, top, label] of [
      [dimX, Y(rBore), `⌀d ${format(geo.d)}`],
      [dimX2, Y(rOuter), `⌀D ${format(geo.D)}`],
    ] as const) {
      ctx.beginPath();
      ctx.moveTo(x, top);
      ctx.lineTo(x, axisY);
      ctx.stroke();
      arrow(x, top, 0, -1);
      arrow(x, axisY, 0, 1);

      ctx.save();
      ctx.translate(x, (top + axisY) / 2);
      ctx.rotate(-Math.PI / 2);
      const w = ctx.measureText(label).width + 10;
      ctx.fillStyle = PALETTE.bg;
      ctx.fillRect(-w / 2, -8, w, 16);
      ctx.fillStyle = PALETTE.dimText;
      ctx.fillText(label, 0, 0);
      ctx.restore();
    }

    // --- corner radius ----------------------------------------------------
    if (this.payload.rMin !== null) {
      const rx = X(geo.B * 0.06);
      const ry = Y(rOuter * 0.98);
      ctx.beginPath();
      ctx.moveTo(rx, ry);
      ctx.lineTo(rx - 26, ry - 22);
      ctx.lineTo(rx - 40, ry - 22);
      ctx.stroke();
      ctx.textAlign = 'right';
      ctx.fillText(`r ${format(this.payload.rMin)}`, rx - 44, ry - 22);
    }

    // --- unit note --------------------------------------------------------
    const unitNote = this.payload.rtl
      ? `همهٔ ابعاد بر حسب ${this.payload.labels.mm}`
      : `All dimensions in ${this.payload.labels.mm}`;
    ctx.font = BearingRenderer.labelFont(unitNote, 11);
    ctx.textAlign = this.payload.rtl ? 'right' : 'left';
    ctx.fillStyle = 'rgba(207,228,251,0.6)';
    ctx.fillText(
      unitNote,
      this.payload.rtl ? this.width - 14 : 14,
      this.height - 14,
    );

    ctx.restore();
  }

  /** ------------------------------------------------------------- thermal */
  private drawThermal(): void {
    const { ctx } = this;
    const limit = this.limitingSpeed();

    const padLeft = 54;
    const padRight = 22;
    const padTop = 26;
    const padBottom = 44;
    const plotW = this.width - padLeft - padRight;
    const plotH = this.height - padTop - padBottom;

    if (limit === null || plotW < 40 || plotH < 40) {
      ctx.save();
      ctx.fillStyle = PALETTE.steelMid;
      ctx.font = '12px system-ui, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(
        this.payload.rtl ? 'حد سرعت برای این شماره ثبت نشده است' : 'No limiting speed published for this reference',
        this.width / 2,
        this.height / 2,
      );
      ctx.restore();
      return;
    }

    const tMin = 20;
    const tMax = 90;
    const px = (ratio: number): number => padLeft + ratio * plotW;
    const py = (celsius: number): number =>
      padTop + plotH - ((celsius - tMin) / (tMax - tMin)) * plotH;

    // --- operating bands --------------------------------------------------
    const bands: Array<[number, number, string, number]> = [
      [0, 0.6, PALETTE.ok, 0.1],
      [0.6, 0.85, PALETTE.warn, 0.12],
      [0.85, 1, PALETTE.danger, 0.14],
    ];
    ctx.save();
    for (const [from, to, colour, alpha] of bands) {
      ctx.globalAlpha = alpha;
      ctx.fillStyle = colour;
      ctx.fillRect(px(from), padTop, px(to) - px(from), plotH);
    }
    ctx.globalAlpha = 1;
    ctx.restore();

    // --- grid and axes ----------------------------------------------------
    ctx.save();
    ctx.strokeStyle = PALETTE.grid;
    ctx.lineWidth = 1;
    ctx.fillStyle = 'rgba(207,228,251,0.66)';
    ctx.font = '10px ui-monospace, monospace';

    for (let t = tMin; t <= tMax; t += 10) {
      const y = py(t);
      ctx.beginPath();
      ctx.moveTo(padLeft, y);
      ctx.lineTo(padLeft + plotW, y);
      ctx.stroke();
      ctx.textAlign = 'right';
      ctx.textBaseline = 'middle';
      ctx.fillText(`${t}°`, padLeft - 8, y);
    }

    for (let r = 0; r <= 1.0001; r += 0.25) {
      const x = px(r);
      ctx.beginPath();
      ctx.moveTo(x, padTop);
      ctx.lineTo(x, padTop + plotH);
      ctx.stroke();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'top';
      ctx.fillText(`${Math.round(limit * r).toLocaleString('en-US')}`, x, padTop + plotH + 8);
    }

    ctx.textAlign = 'center';
    ctx.fillStyle = 'rgba(207,228,251,0.5)';
    ctx.font = BearingRenderer.labelFont(this.payload.labels.rpm, 10);
    ctx.fillText(this.payload.labels.rpm, padLeft + plotW / 2, padTop + plotH + 24);
    ctx.restore();

    // --- the estimate curve ----------------------------------------------
    ctx.save();
    const curve = ctx.createLinearGradient(padLeft, 0, padLeft + plotW, 0);
    curve.addColorStop(0, PALETTE.ok);
    curve.addColorStop(0.6, PALETTE.warn);
    curve.addColorStop(1, PALETTE.danger);
    ctx.strokeStyle = curve;
    ctx.lineWidth = 2.5;
    ctx.beginPath();
    for (let i = 0; i <= 100; i += 1) {
      const ratio = i / 100;
      const celsius = 25 + 48 * Math.pow(ratio, 1.6);
      const x = px(ratio);
      const y = py(celsius);
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    }
    ctx.stroke();
    ctx.restore();

    // --- the operating point ---------------------------------------------
    const estimate = this.estimatedTemperature();
    if (estimate) {
      const ratio = Math.min(1, this.speedFraction);
      const x = px(ratio);
      const y = py(Math.min(tMax, estimate.celsius));

      ctx.save();
      ctx.strokeStyle = 'rgba(144,202,249,0.55)';
      ctx.setLineDash([4, 4]);
      ctx.beginPath();
      ctx.moveTo(x, padTop + plotH);
      ctx.lineTo(x, y);
      ctx.lineTo(padLeft, y);
      ctx.stroke();
      ctx.setLineDash([]);

      ctx.beginPath();
      ctx.arc(x, y, 5.5, 0, Math.PI * 2);
      ctx.fillStyle = PALETTE.accent;
      ctx.fill();
      ctx.strokeStyle = '#fff';
      ctx.lineWidth = 1.5;
      ctx.stroke();
      ctx.restore();
    }

    // --- limiting speed marker -------------------------------------------
    ctx.save();
    ctx.strokeStyle = PALETTE.danger;
    ctx.lineWidth = 1.5;
    ctx.setLineDash([6, 4]);
    ctx.beginPath();
    ctx.moveTo(px(1), padTop);
    ctx.lineTo(px(1), padTop + plotH);
    ctx.stroke();
    ctx.setLineDash([]);
    ctx.restore();
  }
}
