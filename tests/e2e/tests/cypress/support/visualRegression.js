// Tiny PNG-diff helper used by the visual-regression specs. Lives outside
// cypress.config.js so the config file stays a one-liner.
//
// Two modes:
//   1. Global diff (no `regions`) — one threshold across the entire image.
//      Good for layout-heavy pages where any real change moves enough pixels
//      to dominate AA jitter.
//   2. Per-region diff (`regions` array) — pixelmatch each region in
//      isolation. Lets us set a much stricter per-region threshold, because
//      a real icon-glyph swap saturates its small tile while AA jitter
//      stays under a few percent per tile. Without this the icon grid
//      would force the global threshold up to a level that hides single
//      missing/swapped glyphs.
const fs = require("node:fs");
const path = require("node:path");
const { PNG } = require("pngjs");
const pixelmatch = require("pixelmatch");

const PIXELMATCH_OPTS = {
    threshold: 0.2,   // per-pixel color tolerance
    includeAA: false, // skip anti-aliased pixels (GPU-dependent noise)
};

function readPng(p) {
    return PNG.sync.read(fs.readFileSync(p));
}

function cropPng(src, { x, y, width, height }) {
    const out = new PNG({ width, height });
    PNG.bitblt(src, out, x, y, width, height, 0, 0);
    return out;
}

function compareScreenshot({ actualPath, baselinePath, diffPath, regions, regionThreshold = 0.15 }) {
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

    const actual = readPng(actualPath);
    const baseline = readPng(baselinePath);

    if (actual.width !== baseline.width || actual.height !== baseline.height) {
        return { match: false, reason: "size-mismatch", actualSize: [actual.width, actual.height], baselineSize: [baseline.width, baseline.height] };
    }

    if (regions && regions.length) {
        return compareRegions({ actual, baseline, regions, regionThreshold, diffPath });
    }

    return compareGlobal({ actual, baseline, diffPath });
}

function compareGlobal({ actual, baseline, diffPath }) {
    const { width, height } = actual;
    const diff = new PNG({ width, height });
    const diffPixels = pixelmatch(actual.data, baseline.data, diff.data, width, height, PIXELMATCH_OPTS);

    if (diffPath) {
        fs.mkdirSync(path.dirname(diffPath), { recursive: true });
        fs.writeFileSync(diffPath, PNG.sync.write(diff));
    }

    // Allow up to 0.05% of pixels to differ to absorb residual sub-pixel
    // jitter; real component restyles move thousands of pixels and trip this easily.
    const tolerated = Math.floor(width * height * 0.0005);
    return {
        match: diffPixels <= tolerated,
        reason: diffPixels <= tolerated ? "ok" : "pixel-diff",
        diffPixels,
        tolerated,
        totalPixels: width * height,
    };
}

function compareRegions({ actual, baseline, regions, regionThreshold, diffPath }) {
    // Composite diff buffer for the whole image so the saved diff PNG still
    // shows where things drifted, even though the pass/fail decision is
    // per-region.
    const fullDiff = diffPath ? new PNG({ width: actual.width, height: actual.height }) : null;

    const failures = [];
    let totalDiffPixels = 0;
    let totalRegionPixels = 0;

    for (const region of regions) {
        const { name, x, y, width, height } = region;
        const aSlice = cropPng(actual, region);
        const bSlice = cropPng(baseline, region);
        const dSlice = new PNG({ width, height });
        const diffPixels = pixelmatch(aSlice.data, bSlice.data, dSlice.data, width, height, PIXELMATCH_OPTS);

        if (fullDiff) {
            PNG.bitblt(dSlice, fullDiff, 0, 0, width, height, x, y);
        }

        const regionPixels = width * height;
        totalDiffPixels += diffPixels;
        totalRegionPixels += regionPixels;

        const ratio = diffPixels / regionPixels;
        if (ratio > regionThreshold) {
            failures.push({ name, diffPixels, regionPixels, ratio: +ratio.toFixed(4) });
        }
    }

    if (fullDiff) {
        fs.mkdirSync(path.dirname(diffPath), { recursive: true });
        fs.writeFileSync(diffPath, PNG.sync.write(fullDiff));
    }

    return {
        match: failures.length === 0,
        reason: failures.length ? "region-diff" : "ok",
        failures,
        regionThreshold,
        totalDiffPixels,
        totalRegionPixels,
        regionsChecked: regions.length,
    };
}

module.exports = { compareScreenshot };
