(function() {
    const $ = (sel) => document.querySelector(sel);

    function drawBarChart(canvas, data) {
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const W = canvas.clientWidth;
        const H = canvas.clientHeight;
        canvas.width = W * devicePixelRatio;
        canvas.height = H * devicePixelRatio;
        ctx.scale(devicePixelRatio, devicePixelRatio);
        ctx.clearRect(0, 0, W, H);

        const padding = 30;
        const labels = data.map(d => d.label);
        const values = data.map(d => d.value);
        const maxVal = Math.max(10, ...values);
        const barW = Math.max(14, (W - padding * 2) / (values.length || 1) * 0.6);
        const gap = ((W - padding * 2) / (values.length || 1)) - barW;

        ctx.fillStyle = '#2563eb'; // Navy Blue

        values.forEach((v, i) => {
            const x = padding + i * (barW + gap) + gap * 0.5;
            const h = (v / maxVal) * (H - padding * 2);
            ctx.fillRect(Math.round(x), Math.round(H - padding - h), Math.round(barW), Math.round(h));
            ctx.fillText(labels[i], x + barW / 2, H - padding + 10);
        });
    }

    const barCanvas = document.getElementById('barChart');
    
    function renderAll() {
        const sampleData = [
            { label: 'January', value: 20 },
            { label: 'February', value: 30 },
            { label: 'March', value: 25 }
        ];
        drawBarChart(barCanvas, sampleData);
    }

    renderAll();
})();