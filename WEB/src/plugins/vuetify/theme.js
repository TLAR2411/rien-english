// Using a strong, trustworthy "Banking Blue" as the fallback
export const staticPrimaryColor = import.meta.env.VITE_PRIMARY_COLOR ?? '#1A56DB'
export const staticPrimaryDarkenColor = import.meta.env.VITE_PRIMARY_COLOR ?? '#1E429F'

export const themes = {
  light: {
    dark: false,
    colors: {
      'primary': staticPrimaryColor,
      'on-primary': '#ffffff',
      'primary-darken-1': staticPrimaryDarkenColor,

      // Cooler, professional slate-grays for secondary elements
      'secondary': '#64748B',
      'on-secondary': '#ffffff',
      'secondary-darken-1': '#475569',

      // Status colors critical for loans (Approvals, Pending, Overdue)
      'success': '#10B981', // Clean green for "Paid" or "Approved"
      'on-success': '#ffffff',
      'success-darken-1': '#059669',

      'info': '#0EA5E9',
      'on-info': '#ffffff',
      'info-darken-1': '#0284C7',

      'warning': '#F59E0B', // Strong amber for "Pending" or "Due Soon"
      'on-warning': '#ffffff',
      'warning-darken-1': '#D97706',

      'error': '#EF4444', // Sharp red for "Overdue" or "Rejected"
      'on-error': '#ffffff',
      'error-darken-1': '#DC2626',

      // Crisp backgrounds to make financial data grids pop
      'background': '#F8FAFC',
      'on-background': '#334155',
      'surface': '#ffffff',
      'on-surface': '#0F172A',

      // Cool gray scale for borders, dividers, and disabled states
      'grey-50': '#F8FAFC',
      'grey-100': '#F1F5F9',
      'grey-200': '#E2E8F0',
      'grey-300': '#CBD5E1',
      'grey-400': '#94A3B8',
      'grey-500': '#64748B',
      'grey-600': '#475569',
      'grey-700': '#334155',
      'grey-800': '#1E293B',
      'grey-900': '#0F172A',
      'grey-light': '#F8FAFC',
      'perfect-scrollbar-thumb': '#CBD5E1',
      'skin-bordered-background': '#ffffff',
      'skin-bordered-surface': '#ffffff',
      'expansion-panel-text-custom-bg': '#F8FAFC',
    },
    variables: {
      'code-color': '#D946EF',
      'overlay-scrim-background': '#0F172A',
      'tooltip-background': '#1E293B',
      'overlay-scrim-opacity': 0.5,
      'hover-opacity': 0.04,
      'focus-opacity': 0.1,
      'selected-opacity': 0.08,
      'activated-opacity': 0.16,
      'pressed-opacity': 0.14,
      'dragged-opacity': 0.1,
      'disabled-opacity': 0.4,
      'border-color': '#94A3B8', // Lighter borders to keep data tables looking clean
      'border-opacity': 0.25,
      'table-header-color': '#F1F5F9',
      'high-emphasis-opacity': 0.9,
      'medium-emphasis-opacity': 0.7,
      'switch-opacity': 0.2,
      'switch-disabled-track-opacity': 0.3,
      'switch-disabled-thumb-opacity': 0.4,
      'switch-checked-disabled-opacity': 0.3,
      'track-bg': '#E2E8F0',

      // Shadows
      'shadow-key-umbra-color': '#0F172A',
      'shadow-xs-opacity': 0.04,
      'shadow-sm-opacity': 0.06,
      'shadow-md-opacity': 0.08,
      'shadow-lg-opacity': 0.10,
      'shadow-xl-opacity': 0.12,
    },
  },
  dark: {
    dark: true,
    colors: {
      'primary': staticPrimaryColor,
      'on-primary': '#ffffff',
      'primary-darken-1': staticPrimaryDarkenColor,
      'secondary': '#64748B',
      'on-secondary': '#ffffff',
      'secondary-darken-1': '#475569',
      'success': '#10B981',
      'on-success': '#ffffff',
      'success-darken-1': '#059669',
      'info': '#0EA5E9',
      'on-info': '#ffffff',
      'info-darken-1': '#0284C7',
      'warning': '#F59E0B',
      'on-warning': '#ffffff',
      'warning-darken-1': '#D97706',
      'error': '#EF4444',
      'on-error': '#ffffff',
      'error-darken-1': '#DC2626',

      // Deep blue-tinted slate for a premium dark mode (less eye strain than pure black)
      'background': '#0B1121',
      'on-background': '#F1F5F9',
      'surface': '#1E293B',
      'on-surface': '#F8FAFC',

      'grey-50': '#040814',
      'grey-100': '#0B1121',
      'grey-200': '#0F172A',
      'grey-300': '#1E293B',
      'grey-400': '#334155',
      'grey-500': '#475569',
      'grey-600': '#64748B',
      'grey-700': '#94A3B8',
      'grey-800': '#CBD5E1',
      'grey-900': '#E2E8F0',
      'grey-light': '#0F172A',
      'perfect-scrollbar-thumb': '#334155',
      'skin-bordered-background': '#1E293B',
      'skin-bordered-surface': '#1E293B',
    },
    variables: {
      'code-color': '#D946EF',
      'overlay-scrim-background': '#020617',
      'tooltip-background': '#F8FAFC',
      'overlay-scrim-opacity': 0.7,
      'hover-opacity': 0.06,
      'focus-opacity': 0.1,
      'selected-opacity': 0.08,
      'activated-opacity': 0.16,
      'pressed-opacity': 0.14,
      'dragged-opacity': 0.1,
      'disabled-opacity': 0.4,
      'border-color': '#334155',
      'border-opacity': 0.6,
      'table-header-color': '#0F172A',
      'high-emphasis-opacity': 0.9,
      'medium-emphasis-opacity': 0.7,
      'switch-opacity': 0.4,
      'switch-disabled-track-opacity': 0.4,
      'switch-disabled-thumb-opacity': 0.8,
      'switch-checked-disabled-opacity': 0.3,
      'track-bg': '#334155',

      // Shadows
      'shadow-key-umbra-color': '#000000',
      'shadow-xs-opacity': 0.20,
      'shadow-sm-opacity': 0.25,
      'shadow-md-opacity': 0.30,
      'shadow-lg-opacity': 0.35,
      'shadow-xl-opacity': 0.40,
    },
  },
}

export default themes