# Node.js & Frontend Assets

The project uses **Node.js** to compile frontend assets (CSS/JS) using **Vite**.

## Requirements
- Node.js > 18 (LTS v20 recommended)
- NPM (comes with Node)

## Installation (Manual)
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

## Build Commands
Project commands defined in `package.json`:

1. **Install Dependencies**:
   ```bash
   npm install
   ```

2. **Development Server** (Hot Module Replacement):
   ```bash
   npm run dev
   ```

3. **Production Build** (Minified assets):
   ```bash
   npm run build
   ```

## Integration with Laravel
Laravel Blade templates use the `@vite()` directive.
- In **Development**: Loads assets from the Vite dev server (usually port 5173).
- In **Production**: Loads compiled assets from `public/build/`.

Ensure `npm run build` is run before deploying or running in production mode.
