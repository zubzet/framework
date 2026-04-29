// Tiny PNG-diff helper used by the visual-regression specs. Lives outside
// cypress.config.js so the config file stays a one-liner.
//
// The threshold and aa-skipping defaults are tuned for "catch real glyph
// drift, ignore sub-pixel anti-aliasing noise" — different GPUs / driver
// versions render type slightly differently even on the same OS.
const fs = require("node:fs");
const path = require("node:path");
const { PNG } = require("pngjs");
const pixelmatch = require("pixelmatch");

function compareScreenshot({ actualPath, baselinePath, diffPath }) {
    if (!fs.existsSync(actualPath)) {
        return { match: false, reason: "no-actual", actualPath };
    }
    if (!fs.existsSync(baselinePath)) {
        // Convenience: seed the baseline on first run. The test still fails
        // so the human knows to inspect & commit it.
        fs.mkdirSync(path.dirname(baselinePath), { recursive: true });
        fs.copyFileSync(actualPath, baselinePath);
        return { match: false, reason: "no-baseline", baselinePath };
    }

    const actual = PNG.sync.read(fs.readFileSync(actualPath));
    const baseline = PNG.sync.read(fs.readFileSync(baselinePath));

    if (actual.width !== baseline.width || actual.height !== baseline.height) {
        return { match: false, reason: "size-mismatch" };
    }

    const { width, height } = actual;
    const diff = new PNG({ width, height });
    const diffPixels = pixelmatch(actual.data, baseline.data, diff.data, width, height, {
        threshold: 0.2,   // per-pixel color tolerance
        includeAA: false, // skip anti-aliased pixels (GPU-dependent noise)
    });

    if (diffPath) {
        fs.mkdirSync(path.dirname(diffPath), { recursive: true });
        fs.writeFileSync(diffPath, PNG.sync.write(diff));
    }

    // Allow up to 0.05% of pixels to differ to absorb residual sub-pixel
    // jitter; real glyph swaps move thousands of pixels and trip this easily.
    const tolerated = Math.floor(width * height * 0.0005);
    return {
        match: diffPixels <= tolerated,
        reason: diffPixels <= tolerated ? "ok" : "pixel-diff",
        diffPixels,
        tolerated,
        totalPixels: width * height,
    };
}

module.exports = { compareScreenshot };
