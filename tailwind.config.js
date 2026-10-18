/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./app/**/*.php', './public/**/*.php'],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#f4ecff',
          100: '#e9dcff',
          200: '#d8b9ff',
          300: '#ba86ff',
          400: '#9b5cf6',
          500: '#7c39e6',
          600: '#6223c0',
          700: '#4b1d92',
          800: '#2f0f5f',
          900: '#1d0a39'
        },
        midnight: '#090b1a',
        gold: '#f7c948',
        magenta: '#ff4fb3'
      },
      boxShadow: {
        glow: '0 0 30px rgba(255, 79, 179, 0.35)'
      },
      fontFamily: {
        display: ['Georgia', 'serif'],
        body: ['Segoe UI', 'sans-serif']
      },
      backgroundImage: {
        mandala: 'radial-gradient(circle, rgba(255,255,255,0.06) 2px, transparent 2px); background-size: 24px 24px;'
      }
    }
  },
  plugins: []
};
