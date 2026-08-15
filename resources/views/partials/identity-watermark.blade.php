@auth
    @php
        $watermarkUser = auth()->user();
        $watermarkIdentity = trim(($watermarkUser->name ?: '3RD-VN').' · '.($watermarkUser->employee_code ?: $watermarkUser->uid ?: '3RD-VN'));
    @endphp
    <style>
        .identity-watermark {
            position: fixed;
            inset: -24vh -20vw;
            z-index: 10000;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-content: space-around;
            justify-items: center;
            gap: 94px 58px;
            overflow: hidden;
            pointer-events: none;
            user-select: none;
            transform: rotate(-18deg);
            opacity: .09;
            color: #172554;
        }
        .identity-watermark span {
            white-space: nowrap;
            font: 700 26px/1.1 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            letter-spacing: .035em;
        }
        .identity-watermark b { color: #2563eb; font-size: 1.12em; }
        @media (max-width: 760px) {
            .identity-watermark {
                inset: -18vh -48vw;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 76px 28px;
                opacity: .075;
            }
            .identity-watermark span { font-size: 20px; }
        }
        @media print { .identity-watermark { display: none !important; } }
    </style>
    <div class="identity-watermark" aria-hidden="true">
        @foreach (range(1, 24) as $watermarkIndex)
            <span><b>3RD-VN</b> · {{ $watermarkIdentity }}</span>
        @endforeach
    </div>
@endauth
