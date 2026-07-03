/**
 * Password strength bundle entry (re-exports lib for Vite IIFE build).
 */
export * from './password-strength-lib';
export {
  initPasswordStrengthContainer,
  runInit,
  runInitAndObserve,
} from './password-strength';
