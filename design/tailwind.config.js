/** Salma Drive — Tailwind theme extension
 *  Merge `theme.extend` into your tailwind.config.js. Uses CSS variables from
 *  tokens/theme.css so light/dark switch automatically. darkMode: 'class'.
 *  Semantic utilities (bg-surface, text-muted, border-base, text-brand, …) keep
 *  markup clean and theme-safe — prefer them over raw palette in components. */

const rgb = (v) => `rgb(var(${v}) / <alpha-value>)`;

module.exports = {
  darkMode: 'class',
  theme: {
    container: { center: true, padding: '1rem', screens: { '2xl': '1280px', '3xl': '1440px' } },
    screens: {
      sm: '640px', md: '768px', lg: '1024px', xl: '1280px', '2xl': '1536px', '3xl': '1920px',
    },
    extend: {
      colors: {
        /* static palette (fine control) */
        brand: {
          50:'#FDF6E7',100:'#FBEAC2',200:'#F7D98A',300:'#F4C95C',400:'#F2B93C',
          500:'#F0A820',600:'#D08D12',700:'#A66E0E',800:'#7D530C',900:'#5C3D0A',
          DEFAULT: rgb('--brand'),
        },
        neutral: {
          0:'#FFFFFF',50:'#F6F7F9',100:'#ECEEF1',200:'#DCDFE5',300:'#C8CDD6',
          400:'#9CA3B0',500:'#5A6070',600:'#454B59',700:'#313643',800:'#1A1A24',
          900:'#0F0F16',950:'#080810',
        },
        success: { DEFAULT: rgb('--success'), subtle:'#E7F8F0', strong:'#1E8A58' },
        info:    { DEFAULT: rgb('--info'),    subtle:'#E6F6FD', strong:'#1F8FBE' },
        danger:  { DEFAULT: rgb('--danger'),  subtle:'#FBE7ED', strong:'#A81D47' },
        warning: { DEFAULT: rgb('--warning'), subtle:'#FDEFE2', strong:'#B85B12' },

        /* semantic (theme-aware) — preferred in markup */
        base: rgb('--bg-base'),
        surface: rgb('--bg-surface'),
        'surface-2': rgb('--bg-surface-2'),
        sidebar: rgb('--bg-sidebar'),
        'brand-hover': rgb('--brand-hover'),
        'on-brand': rgb('--text-on-brand'),
      },
      textColor: {
        strong: rgb('--text-strong'),
        DEFAULT: rgb('--text'),
        muted: rgb('--text-muted'),
        brand: rgb('--brand'),
        'on-brand': rgb('--text-on-brand'),
      },
      backgroundColor: {
        base: rgb('--bg-base'),
        surface: rgb('--bg-surface'),
        'surface-2': rgb('--bg-surface-2'),
        sidebar: rgb('--bg-sidebar'),
        brand: rgb('--brand'),
        'brand-hover': rgb('--brand-hover'),
      },
      borderColor: {
        base: rgb('--border'),
        strong: rgb('--border-strong'),
        brand: rgb('--brand'),
        DEFAULT: rgb('--border'),
      },
      ringColor: { brand: rgb('--ring'), DEFAULT: rgb('--ring') },
      fontFamily: {
        display: ['Sora', 'system-ui', 'sans-serif'],
        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
        mono: ['IBM Plex Mono', 'ui-monospace', 'monospace'],
      },
      fontSize: {
        'display-2xl': ['3.5rem',  { lineHeight:'1.05', fontWeight:'700' }],
        'display-xl':  ['2.75rem', { lineHeight:'1.1',  fontWeight:'700' }],
        'display-lg':  ['2.25rem', { lineHeight:'1.1',  fontWeight:'700' }],
        h1: ['1.875rem', { lineHeight:'1.2',  fontWeight:'600' }],
        h2: ['1.5rem',   { lineHeight:'1.25', fontWeight:'600' }],
        h3: ['1.25rem',  { lineHeight:'1.3',  fontWeight:'600' }],
        h4: ['1.125rem', { lineHeight:'1.4',  fontWeight:'600' }],
        'body-lg': ['1.0625rem', { lineHeight:'1.6' }],
        body: ['1rem', { lineHeight:'1.6' }],
        'body-sm': ['0.875rem', { lineHeight:'1.55' }],
        caption: ['0.75rem', { lineHeight:'1.5', fontWeight:'500' }],
        overline: ['0.6875rem', { lineHeight:'1.4', fontWeight:'600', letterSpacing:'0.08em' }],
        price: ['1.5rem', { lineHeight:'1.1', fontWeight:'700' }],
      },
      borderRadius: { sm:'6px', md:'10px', lg:'14px', xl:'20px', '2xl':'28px', full:'9999px' },
      boxShadow: {
        e1: 'var(--shadow-e1)', e2: 'var(--shadow-e2)',
        e3: 'var(--shadow-e3)', e4: 'var(--shadow-e4)',
      },
      transitionTimingFunction: { standard: 'cubic-bezier(.2,0,0,1)' },
      transitionDuration: { 150:'150ms', 200:'200ms', 300:'300ms' },
      zIndex: { dropdown:'1000', sticky:'1020', drawer:'1030', modal:'1040', toast:'1050', tooltip:'1060' },
    },
  },
  plugins: [/* @tailwindcss/forms recommended for input resets */],
};
