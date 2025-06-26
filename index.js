const express = require('express');
const axios = require('axios');
const app = express();
const port = process.env.PORT || 3000;

const INFINITYFREE_URL = 'https://futube.great-site.net/api/get_featured_videos.php';

app.get('/api/get_featured_videos', async (req, res) => {
    try {
        const targetUrl = `${INFINITYFREE_URL}?${new URLSearchParams(req.query).toString()}`;
        let responseData;
        let isJson = false;

        for (let attempt = 1; attempt <= 2; attempt++) {
            try {
                const response = await axios.get(targetUrl);
                responseData = response.data;

                // حاول تحليل الاستجابة كـ JSON
                if (typeof responseData === 'string') {
                    try {
                        responseData = JSON.parse(responseData);
                        isJson = true; // تم التحليل بنجاح
                        break; // خرج من حلقة المحاولات، لأننا حصلنا على JSON صالح
                    } catch (e) {
                        // ليس JSON صالحًا، استمر في التحقق من الإعلانات أو أعد المحاولة
                        isJson = false;
                    }
                } else if (typeof responseData === 'object') {
                    // إذا كانت الاستجابة بالفعل كائنًا (axios قد يحلل JSON تلقائيًا)
                    isJson = true;
                    break;
                }

                // إذا لم تكن JSON، تحقق من وجود إعلانات/ReCaptcha
                if (typeof responseData === 'string' && (responseData.includes('recaptcha_style.css') || responseData.includes('google.com/recaptcha/api.js'))) {
                    console.warn(`Attempt ${attempt}: InfinityFree reCAPTCHA/ad detected. Retrying...`);
                    await new Promise(resolve => setTimeout(resolve, 1000));
                } else {
                    // إذا لم تكن JSON وليست إعلانًا معروفًا، اعتبرها استجابة غير متوقعة
                    console.error(`Attempt ${attempt}: Unexpected non-JSON response from InfinityFree.`);
                    // لا تحاول مرة أخرى إذا لم يكن JSON ولم يكن إعلانًا معروفًا
                    // أو يمكنك هنا محاولة استخراج JSON إذا كنت تعرف نمطًا محددًا لـ HTML غير الإعلاني
                    break;
                }
            } catch (error) {
                console.warn(`Attempt ${attempt}: Request to InfinityFree failed. Retrying...`, error.message);
                if (attempt === 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                } else {
                    throw error; // أعد رمي الخطأ إذا فشلت المحاولة الثانية
                }
            }
        }

        if (isJson) {
            // إذا كانت الاستجابة JSON صالحة، أرسلها إلى العميل
            res.setHeader('Content-Type', 'application/json');
            res.status(200).json(responseData);
        } else {
            // إذا لم يتم الحصول على JSON صالح بعد المحاولتين
            console.error('Failed to retrieve valid JSON from InfinityFree after multiple attempts.');
            res.status(500).json({ error: 'Failed to retrieve valid JSON data from origin server.', details: 'Received non-JSON response.' });
        }

    } catch (error) {
        console.error('Error in proxy request:', error.message);
        res.status(500).json({ error: 'An error occurred while processing your request.', details: error.message });
    }
});

app.listen(port, () => {
    console.log(`Proxy server listening at http://localhost:${port}`);
});
