/**
 * Shared types for password strength evaluation.
 */

export type FeedbackPosition = 'above' | 'below';

export type UiFramework =
  | 'default'
  | 'bootstrap3'
  | 'bootstrap4'
  | 'bootstrap5'
  | 'tailwind2'
  | 'foundation5'
  | 'foundation6';

export interface PasswordConditionsConfig {
  min_length?: number;
  max_length?: number | null;
  require_lowercase?: boolean;
  lowercase?: boolean;
  require_uppercase?: boolean;
  uppercase?: boolean;
  require_digit?: boolean;
  digit?: boolean;
  require_special?: boolean;
  special?: boolean;
  special_chars?: string;
  disallow_whitespace?: boolean;
  no_whitespace?: boolean;
  not_contain?: string[];
  regex?: string | null;
  regex_message?: string | null;
  min_unique_chars?: number;
}

export interface RequirementResult {
  id: string;
  labelKey: string;
  met: boolean;
  current?: number;
  required?: number;
}

export interface EvaluationResult {
  valid: boolean;
  missing: string[];
  pattern: string;
  requirements: RequirementResult[];
}

export type GeneratorMode = 'off' | 'input' | 'modal';

export interface PasswordStrengthLabels {
  [key: string]: string;
}

export interface PasswordStrengthConfig {
  conditions: PasswordConditionsConfig;
  pattern: string;
  feedbackPosition: FeedbackPosition;
  showRequirements: boolean;
  liveFeedback: boolean;
  translationDomain: string;
  uiFramework: UiFramework;
  generatorMode: GeneratorMode;
  generatorCount: number;
  labels: PasswordStrengthLabels;
}
