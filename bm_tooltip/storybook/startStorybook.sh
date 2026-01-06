cd web/modules/custom/bm_tooltip/storybook
npm install
npm install @storybook/html-vite --save-dev
npx storybook dev -p 6006 --host 0.0.0.0 --ci

echo "access it at http://127.0.0.1:6006"
