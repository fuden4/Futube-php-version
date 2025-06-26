// هذا الكود يتطلب تثبيت مكتبة 'express' و 'axios'
// Vercel يقوم بتثبيتها تلقائيًا عند النشر إذا كانت مذكورة في package.json
// (ولكن في حالتنا، Vercel ذكي بما يكفي لاكتشافها حتى بدون package.json صريح للمشاريع البسيطة)

const express = require('express');
const axios = require('axios');
const app = express();
const port = process.env.PORT || 3000; // استخدم المنفذ الذي يحدده المضيف أو 3000 افتراضيًا

// الرابط الأصلي في InfinityFree
const INFINITYFREE_URL = 'https://futube.great-site.net/api/get_featured_videos.php';

app.get('/api/get_featured_videos', async (req, res) => {
    try {
        // بناء الرابط بالبارامترات المستلمة من تطبيق الأندرويد
        // في حالتك، سيتم تمرير '?i=1' كـ query parameter
        const targetUrl = `${INFINITYFREE_URL}?${new URLSearchParams(req.query).toString()}`;

        let response;
        try {
            // المحاولة الأولى لجلب البيانات
            response = await axios.get(targetUrl);
        } catch (error) {
            // إذا حدث خطأ في المحاولة الأولى (مثلاً بسبب ReCaptcha)، حاول مرة أخرى
            console.warn('First attempt failed, retrying...', error.message);
            // انتظر قليلاً قبل المحاولة الثانية
            await new Promise(resolve => setTimeout(resolve, 1000));
            response = await axios.get(targetUrl);
        }

        // تحقق مما إذا كانت الاستجابة تحتوي على ReCaptcha أو إعلانات InfinityFree
        const responseData = response.data;
        if (typeof responseData === 'string' && (responseData.includes('recaptcha_style.css') || responseData.includes('google.com/recaptcha/api.js'))) {
            // إذا كانت تحتوي على إعلان، يمكنك إرجاع خطأ أو رسالة خاصة
            console.error('InfinityFree reCAPTCHA/ad detected in response.');
            return res.status(500).json({ error: 'Failed to retrieve clean data: ReCAPTCHA/Ad detected from origin server.' });
        }

        // إذا وصلت إلى هنا، فالاستجابة نظيفة (أو على الأقل لا تحتوي على إعلان ReCaptcha الواضح)
        // قم بإرسال الاستجابة كما هي
        res.setHeader('Content-Type', response.headers['content-type']); // حافظ على نوع المحتوى الأصلي
        res.status(response.status).send(responseData);

    } catch (error) {
        // التعامل مع أي أخطاء أخرى (مثل عدم القدرة على الاتصال بـ InfinityFree)
        console.error('Error in proxy request:', error.message);
        res.status(500).json({ error: 'An error occurred while processing your request.', details: error.message });
    }
});

// هذا السطر مهم لكي يبدأ الخادم بالاستماع للطلبات
app.listen(port, () => {
    console.log(`Proxy server listening at http://localhost:${port}`);
});