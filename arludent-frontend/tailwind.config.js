/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#F0F7FF',
          100: '#DCEEFB',
          200: '#B9DCF7',
          300: '#7DC2EF',
          400: '#3EA6E3',
          500: '#1A8FD6',
          600: '#0E6FB5',
          700: '#0C5A94',
          800: '#0D4B7A',
          900: '#103F66',
          950: '#0A2A45',
        },
        dental: {
          50: '#EFFEFA',
          100: '#D0FBF0',
          200: '#A3F5E0',
          300: '#6AEACB',
          400: '#32D4B1',
          500: '#14B89C',
          600: '#0A957E',
          700: '#0C7767',
          800: '#0E5E53',
          900: '#104D45',
        },
        accent: {
          50: '#FFF9EB',
          100: '#FFF0CC',
          200: '#FFDF8A',
          300: '#FFCC47',
          400: '#F5B820',
          500: '#D4A017',
          600: '#B8860B',
          700: '#946B09',
          800: '#7A5608',
          900: '#654709',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
        'soft-lg': '0 10px 40px -10px rgba(0, 0, 0, 0.1), 0 2px 10px -2px rgba(0, 0, 0, 0.04)',
        'soft-xl': '0 20px 60px -15px rgba(0, 0, 0, 0.12), 0 4px 15px -3px rgba(0, 0, 0, 0.05)',
        'primary-sm': '0 2px 8px -2px rgba(14, 111, 181, 0.2)',
        'primary': '0 4px 14px -3px rgba(14, 111, 181, 0.3)',
        'primary-lg': '0 8px 25px -5px rgba(14, 111, 181, 0.35)',
        'dental': '0 4px 14px -3px rgba(10, 149, 126, 0.3)',
        'accent': '0 4px 14px -3px rgba(212, 160, 23, 0.3)',
        'danger': '0 4px 14px -3px rgba(239, 68, 68, 0.3)',
        'inner-glow': 'inset 0 1px 0 0 rgba(255, 255, 255, 0.05)',
        'sidebar': '4px 0 25px -5px rgba(10, 42, 69, 0.3)',
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.5rem',
      },
      animation: {
        'fade-in': 'fadeIn 0.4s ease-out',
        'fade-in-up': 'fadeInUp 0.5s ease-out',
        'slide-up': 'slideUp 0.5s ease-out',
        'slide-in-left': 'slideInLeft 0.3s ease-out',
        'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
        'float': 'float 3s ease-in-out infinite',
        'shimmer': 'shimmer 2s linear infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        fadeInUp: {
          '0%': { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideInLeft: {
          '0%': { opacity: '0', transform: 'translateX(-20px)' },
          '100%': { opacity: '1', transform: 'translateX(0)' },
        },
        pulseSoft: {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.7' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-5px)' },
        },
        shimmer: {
          '0%': { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
      },
    },
  },
  plugins: [],
}
