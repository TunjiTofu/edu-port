<style>
    /* Bottom margin leaves room for the page number stamped by PortfolioPdfBuilder. */
    @page { margin: 16mm 16mm 18mm 16mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; line-height: 1.5; color: #222; }
    h1, h2, h3, p { margin: 0; }
    table { width: 100%; border-collapse: collapse; }

    .strip { border-bottom: 2px solid #003DA5; padding-bottom: 4px; margin-bottom: 8mm; font-size: 9px; color: #555; }
    .strip td { padding: 0; }
    .strip .right { text-align: right; }

    .block-title { background: #003DA5; color: #fff; padding: 5px 10px; font-size: 12px; font-weight: bold; margin: 0 0 6px; }
    .details td { padding: 6px 8px; border: 1px solid #dde3ee; vertical-align: top; }
    .details td.label { width: 32%; background: #f3f6fb; font-weight: bold; color: #445; }
    .summary td { width: 25%; text-align: center; padding: 8px 4px; border: 1px solid #003DA5; background: #eef3fc; }
    .summary .k { font-size: 9px; color: #555; }
    .summary .v { font-size: 17px; font-weight: bold; color: #003DA5; }
    .note { font-size: 9px; color: #666; margin-top: 6px; }
    .muted { color: #888; font-style: italic; }

    .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
    .b-reviewed { background: #e2f0d9; color: #2e6b12; }
    .b-pending  { background: #fff4d6; color: #8a6d00; }
    .b-revision { background: #ffe7e7; color: #a00000; }
    .b-resub    { background: #efe5fb; color: #5b2a91; }
    .b-original { background: #eef0f3; color: #555; }
    .page-break { page-break-after: always; }
</style>
