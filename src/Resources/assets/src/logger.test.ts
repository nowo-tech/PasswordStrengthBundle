import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createBundleLogger } from './logger';

describe('logger', () => {
  beforeEach(() => {
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'debug').mockImplementation(() => {});
    vi.spyOn(console, 'info').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  it('scriptLoaded logs without build time when options empty', () => {
    const log = createBundleLogger('password-strength');
    log.scriptLoaded();
    expect(console.log).toHaveBeenCalledWith(
      expect.stringContaining('script loaded'),
      expect.any(String),
    );
  });

  it('scriptLoaded logs with build time when buildTime provided', () => {
    const log = createBundleLogger('password-strength', {
      buildTime: '2026-01-15T12:00:00.000Z',
    });
    log.scriptLoaded();
    expect(console.log).toHaveBeenCalledWith(
      expect.stringContaining('script loaded'),
      expect.any(String),
      'color:#059669',
    );
    expect(console.log).toHaveBeenCalledWith(
      expect.stringContaining('2026-01-15T12:00:00.000Z'),
      expect.any(String),
      expect.any(String),
    );
  });

  it('debug is silent until setDebug(true)', () => {
    const log = createBundleLogger('password-strength');
    log.debug('msg');
    expect(console.debug).not.toHaveBeenCalled();

    log.setDebug(true);
    log.debug('msg');
    expect(console.debug).toHaveBeenCalled();
  });
});
