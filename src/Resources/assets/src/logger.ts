/**
 * =============================================================================
 * BUNDLE LOGGER - Password Strength Bundle
 * =============================================================================
 *
 * Same API as other Nowo bundles (TwigInspectorBundle, IconSelectorBundle).
 *
 * USAGE:
 *
 * 1. Create a logger in the entry file:
 *
 *    import { createBundleLogger } from './logger';
 *    declare const __PASSWORD_STRENGTH_BUILD_TIME__: string;
 *
 *    const log = createBundleLogger('password-strength', {
 *      buildTime: typeof __PASSWORD_STRENGTH_BUILD_TIME__ !== 'undefined' ? __PASSWORD_STRENGTH_BUILD_TIME__ : undefined,
 *    });
 *
 * 2. Log once at startup:
 *
 *    log.scriptLoaded();
 *
 * 3. Call setDebug(true) to enable debug/info/warn/error output.
 *
 * 4. Inject build time in vite.config.ts:
 *
 *    define: {
 *      __PASSWORD_STRENGTH_BUILD_TIME__: JSON.stringify(new Date().toISOString()),
 *    },
 * =============================================================================
 */

export type BundleLoggerOptions = {
  /** If set, scriptLoaded() will include this (e.g. build/compilation time). */
  buildTime?: string;
};

export type BundleLogger = {
  /** Call once at startup. Logs "script loaded" and optional build time. Always shown. */
  scriptLoaded: () => void;
  /** When false, debug/info/warn/error are no-ops (only scriptLoaded is shown). */
  setDebug: (enabled: boolean) => void;
  debug: (...args: unknown[]) => void;
  info: (...args: unknown[]) => void;
  warn: (...args: unknown[]) => void;
  error: (...args: unknown[]) => void;
};

const STYLES = {
  script: 'color:#0ea5e9;font-weight:bold',
  debug: 'color:#6b7280',
  info: 'color:#2563eb',
  warn: 'color:#d97706',
  error: 'color:#dc2626;font-weight:bold',
} as const;

const EMOJI = {
  script: '📦',
  debug: '🔍',
  info: 'ℹ️',
  warn: '⚠️',
  error: '❌',
} as const;

function formatArgs(args: unknown[]): unknown[] {
  return args.map((a) =>
    typeof a === 'object' && a !== null && !(a instanceof Error) ? JSON.stringify(a) : a,
  );
}

/**
 * Creates a logger for the Password Strength bundle.
 *
 * @param name - Short name for log prefix (e.g. 'password-strength').
 * @param options - Optional buildTime for scriptLoaded().
 */
export function createBundleLogger(name: string, options: BundleLoggerOptions = {}): BundleLogger {
  const prefix = `[${name}]`;
  const { buildTime } = options;
  let debugEnabled = false;

  return {
    scriptLoaded(): void {
      if (buildTime !== undefined && buildTime !== '') {
        console.log(
          `%c${EMOJI.script} ${prefix} script loaded, build time: %c${buildTime}`,
          STYLES.script,
          'color:#059669',
        );
      } else {
        console.log(`%c${EMOJI.script} ${prefix} script loaded`, STYLES.script);
      }
    },

    setDebug(enabled: boolean): void {
      debugEnabled = enabled;
    },

    debug(...args: unknown[]): void {
      if (!debugEnabled) return;
      if (args.length > 0) {
        console.debug(`%c${EMOJI.debug} ${prefix}`, STYLES.debug, ...formatArgs(args));
      } else {
        console.debug(`%c${EMOJI.debug} ${prefix}`, STYLES.debug);
      }
    },

    info(...args: unknown[]): void {
      if (!debugEnabled) return;
      if (args.length > 0) {
        console.info(`%c${EMOJI.info} ${prefix}`, STYLES.info, ...formatArgs(args));
      } else {
        console.info(`%c${EMOJI.info} ${prefix}`, STYLES.info);
      }
    },

    warn(...args: unknown[]): void {
      if (!debugEnabled) return;
      if (args.length > 0) {
        console.warn(`%c${EMOJI.warn} ${prefix}`, STYLES.warn, ...formatArgs(args));
      } else {
        console.warn(`%c${EMOJI.warn} ${prefix}`, STYLES.warn);
      }
    },

    error(...args: unknown[]): void {
      if (!debugEnabled) return;
      if (args.length > 0) {
        console.error(`%c${EMOJI.error} ${prefix}`, STYLES.error, ...formatArgs(args));
      } else {
        console.error(`%c${EMOJI.error} ${prefix}`, STYLES.error);
      }
    },
  };
}
