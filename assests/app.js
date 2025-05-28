 
        // Sample video data
        const videos = [
            {
                id: 1,
                title: "فيلم الخيال العلمي الجديد",
                category: "movies",
                views: "2.3M",
                duration: "2h 15m",
                gradient: "linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
            },
            {
                id: 2,
                title: "مسلسل الدراما الحديث",
                category: "series",
                views: "5.1M",
                duration: "45m",
                gradient: "linear-gradient(135deg, #f093fb 0%, #f5576c 100%)"
            },
            {
                id: 3,
                title: "وثائقي عن الطبيعة",
                category: "documentaries",
                views: "1.8M",
                duration: "1h 30m",
                gradient: "linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)"
            },
            {
                id: 4,
                title: "أنمي الأكشن المثير",
                category: "anime",
                views: "3.7M",
                duration: "24m",
                gradient: "linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)"
            },
            {
                id: 5,
                title: "بث مباشر - كرة القدم",
                category: "live",
                views: "LIVE",
                duration: "مباشر",
                gradient: "linear-gradient(135deg, #fa709a 0%, #fee140 100%)"
            },
            {
                id: 6,
                title: "كوميديا عائلية",
                category: "movies",
                views: "4.2M",
                duration: "1h 45m",
                gradient: "linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)"
            },
            {
                id: 7,
                title: "مسلسل تشويق وإثارة",
                category: "series",
                views: "6.5M",
                duration: "50m",
                gradient: "linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)"
            },
            {
                id: 8,
                title: "وثائقي تاريخي",
                category: "documentaries",
                views: "2.1M",
                duration: "2h",
                gradient: "linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)"
            }
        ];

        // Render videos
        function renderVideos(videosToRender = videos) {
            const videoGrid = document.getElementById('videoGrid');
            videoGrid.innerHTML = '';

            videosToRender.forEach(video => {
                const videoCard = document.createElement('div');
                videoCard.className = 'video-card';
                videoCard.innerHTML = `
                    <div class="video-thumbnail" style="background: ${video.gradient};">
                        <div class="play-button" onclick="playVideo(${video.id})"></div>
                    </div>
                    <div class="video-info">
                        <div class="video-title">${video.title}</div>
                        <div class="video-meta">${video.views} مشاهدة • ${video.duration}</div>
                    </div>
                `;
                videoGrid.appendChild(videoCard);
            });
        }

        // Filter content by category
        function filterContent(category) {
            // Update active button
            document.querySelectorAll('.category-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filter videos
            const filteredVideos = category === 'all' 
                ? videos 
                : videos.filter(video => video.category === category);
            
            renderVideos(filteredVideos);
        }

        // Play video function
        function playVideo(videoId) {
            const video = videos.find(v => v.id === videoId);
            showVideoPage(video);
        }

        // Show video player page
        function showVideoPage(video) {
            const mainContent = document.querySelector('body');
            
            // Create video page HTML
            const videoPageHTML = `
                <div id="videoPage" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100vh;
                    background: linear-gradient(135deg, #0a0e27 0%, #1a1a2e 50%, #16213e 100%);
                    z-index: 2000;
                    overflow-y: auto;
                    animation: slideInRight 0.5s ease-out;
                ">
                    <!-- Video Page Header -->
                    <div style="
                        background: rgba(0, 0, 0, 0.9);
                        backdrop-filter: blur(20px);
                        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                        padding: 1rem 2rem;
                        position: sticky;
                        top: 0;
                        z-index: 100;
                    ">
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            max-width: 1400px;
                            margin: 0 auto;
                        ">
                            <button onclick="closeVideoPage()" style="
                                background: rgba(255, 255, 255, 0.1);
                                border: 1px solid rgba(255, 255, 255, 0.2);
                                color: white;
                                padding: 0.5rem 1rem;
                                border-radius: 25px;
                                cursor: pointer;
                                transition: all 0.3s ease;
                                font-size: 1rem;
                            " onmouseover="this.style.background='linear-gradient(45deg, #ff6b6b, #4ecdc4)'; this.style.borderColor='transparent';" 
                               onmouseout="this.style.background='rgba(255, 255, 255, 0.1)'; this.style.borderColor='rgba(255, 255, 255, 0.2)';">
                                ← العودة
                            </button>
                            <div style="
                                font-size: 1.5rem;
                                font-weight: bold;
                                background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
                                -webkit-background-clip: text;
                                -webkit-text-fill-color: transparent;
                                background-clip: text;
                            ">FUDEN</div>
                            <div style="width: 80px;"></div>
                        </div>
                    </div>

                    <!-- Video Player Section -->
                    <div style="max-width: 1400px; margin: 0 auto; padding: 2rem;">
                        <div style="
                            background: rgba(255, 255, 255, 0.05);
                            backdrop-filter: blur(10px);
                            border-radius: 20px;
                            border: 1px solid rgba(255, 255, 255, 0.1);
                            overflow: hidden;
                            margin-bottom: 2rem;
                        ">
                            <!-- Video Player -->
                            <div style="
                                aspect-ratio: 16/9;
                                background: ${video.gradient};
                                position: relative;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <div style="
                                    background: rgba(0, 0, 0, 0.7);
                                    backdrop-filter: blur(10px);
                                    border-radius: 15px;
                                    padding: 2rem;
                                    text-align: center;
                                    border: 1px solid rgba(255, 255, 255, 0.1);
                                ">
                                    <div style="
                                        width: 80px;
                                        height: 80px;
                                        background: rgba(255, 255, 255, 0.9);
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        margin: 0 auto 1rem auto;
                                        cursor: pointer;
                                        transition: all 0.3s ease;
                                    " onclick="startVideo()" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
                                        <span style="color: #333; font-size: 2rem; margin-left: 5px;">▶</span>
                                    </div>
                                    <h3 style="color: white; margin-bottom: 0.5rem;">اضغط للتشغيل</h3>
                                    <p style="color: rgba(255, 255, 255, 0.7);">جودة عالية HD</p>
                                </div>

                                <!-- Live indicator for live videos -->
                                ${video.category === 'live' ? `
                                    <div style="
                                        position: absolute;
                                        top: 1rem;
                                        right: 1rem;
                                        background: linear-gradient(45deg, #ff6b6b, #ff4757);
                                        color: white;
                                        padding: 0.5rem 1rem;
                                        border-radius: 20px;
                                        font-weight: bold;
                                        animation: pulse 2s infinite;
                                    ">
                                        🔴 مباشر
                                    </div>
                                ` : ''}
                            </div>

                            <!-- Video Controls -->
                            <div style="
                                padding: 1.5rem;
                                background: rgba(0, 0, 0, 0.5);
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                flex-wrap: wrap;
                                gap: 1rem;
                            ">
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <button style="
                                        background: rgba(255, 255, 255, 0.1);
                                        border: 1px solid rgba(255, 255, 255, 0.2);
                                        color: white;
                                        padding: 0.5rem 1rem;
                                        border-radius: 15px;
                                        cursor: pointer;
                                        font-size: 0.9rem;
                                    ">🔊 الصوت</button>
                                    <button style="
                                        background: rgba(255, 255, 255, 0.1);
                                        border: 1px solid rgba(255, 255, 255, 0.2);
                                        color: white;
                                        padding: 0.5rem 1rem;
                                        border-radius: 15px;
                                        cursor: pointer;
                                        font-size: 0.9rem;
                                    ">⚙️ الجودة</button>
                                    <button style="
                                        background: rgba(255, 255, 255, 0.1);
                                        border: 1px solid rgba(255, 255, 255, 0.2);
                                        color: white;
                                        padding: 0.5rem 1rem;
                                        border-radius: 15px;
                                        cursor: pointer;
                                        font-size: 0.9rem;
                                    ">🔄 السرعة</button>
                                </div>
                                <button style="
                                    background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
                                    border: none;
                                    color: white;
                                    padding: 0.5rem 1rem;
                                    border-radius: 15px;
                                    cursor: pointer;
                                    font-size: 0.9rem;
                                    font-weight: bold;
                                ">📺 ملء الشاشة</button>
                            </div>
                        </div>

                        <!-- Video Info -->
                        <div style="
                            display: grid;
                            grid-template-columns: 2fr 1fr;
                            gap: 2rem;
                            margin-bottom: 2rem;
                        ">
                            <!-- Video Details -->
                            <div style="
                                background: rgba(255, 255, 255, 0.05);
                                backdrop-filter: blur(10px);
                                border-radius: 20px;
                                border: 1px solid rgba(255, 255, 255, 0.1);
                                padding: 2rem;
                            ">
                                <h1 style="
                                    font-size: 2rem;
                                    margin-bottom: 1rem;
                                    background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
                                    -webkit-background-clip: text;
                                    -webkit-text-fill-color: transparent;
                                    background-clip: text;
                                ">${video.title}</h1>
                                
                                <div style="
                                    display: flex;
                                    gap: 2rem;
                                    margin-bottom: 1.5rem;
                                    color: rgba(255, 255, 255, 0.7);
                                    flex-wrap: wrap;
                                ">
                                    <span>👁️ ${video.views} مشاهدة</span>
                                    <span>⏰ ${video.duration}</span>
                                    <span>📅 ${new Date().toLocaleDateString('ar-SA')}</span>
                                </div>

                                <div style="
                                    display: flex;
                                    gap: 1rem;
                                    margin-bottom: 2rem;
                                    flex-wrap: wrap;
                                ">
                                    <button style="
                                        background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
                                        border: none;
                                        color: white;
                                        padding: 0.8rem 2rem;
                                        border-radius: 25px;
                                        cursor: pointer;
                                        font-weight: bold;
                                        transition: all 0.3s ease;
                                    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(255, 107, 107, 0.4)';"
                                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                        👍 أعجبني
                                    </button>
                                    <button style="
                                        background: rgba(255, 255, 255, 0.1);
                                        border: 1px solid rgba(255, 255, 255, 0.2);
                                        color: white;
                                        padding: 0.8rem 2rem;
                                        border-radius: 25px;
                                        cursor: pointer;
                                        transition: all 0.3s ease;
                                    ">💾 حفظ</button>
                                    <button style="
                                        background: rgba(255, 255, 255, 0.1);
                                        border: 1px solid rgba(255, 255, 255, 0.2);
                                        color: white;
                                        padding: 0.8rem 2rem;
                                        border-radius: 25px;
                                        cursor: pointer;
                                        transition: all 0.3s ease;
                                    ">📤 مشاركة</button>
                                </div>

                                <div style="
                                    background: rgba(255, 255, 255, 0.05);
                                    border-radius: 15px;
                                    padding: 1.5rem;
                                    border: 1px solid rgba(255, 255, 255, 0.1);
                                ">
                                    <h3 style="margin-bottom: 1rem; color: #4ecdc4;">وصف الفيديو</h3>
                                    <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                                        ${getVideoDescription(video)}
                                    </p>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div>
                                <h3 style="
                                    margin-bottom: 1.5rem;
                                    color: #4ecdc4;
                                    font-size: 1.3rem;
                                ">فيديوهات مقترحة</h3>
                                
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    ${generateSuggestedVideos(video.id)}
                                </div>
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div style="
                            background: rgba(255, 255, 255, 0.05);
                            backdrop-filter: blur(10px);
                            border-radius: 20px;
                            border: 1px solid rgba(255, 255, 255, 0.1);
                            padding: 2rem;
                        ">
                            <h3 style="margin-bottom: 1.5rem; color: #4ecdc4;">التعليقات</h3>
                            
                            <!-- Add comment -->
                            <div style="
                                display: flex;
                                gap: 1rem;
                                margin-bottom: 2rem;
                                align-items: flex-start;
                            ">
                                <div style="
                                    width: 40px;
                                    height: 40px;
                                    background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-weight: bold;
                                    color: white;
                                ">👤</div>
                                <div style="flex: 1;">
                                    <textarea placeholder="اكتب تعليقك هنا..." style="
                                        width: 100%;
                                        background: rgba(255, 255, 255, 0.1);
                                        border: 1px solid rgba(255, 255, 255, 0.2);
                                        border-radius: 15px;
                                        padding: 1rem;
                                        color: white;
                                        font-size: 1rem;
                                        resize: vertical;
                                        min-height: 80px;
                                    "></textarea>
                                    <button style="
                                        background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
                                        border: none;
                                        color: white;
                                        padding: 0.8rem 2rem;
                                        border-radius: 15px;
                                        cursor: pointer;
                                        font-weight: bold;
                                        margin-top: 1rem;
                                    ">نشر التعليق</button>
                                </div>
                            </div>

                            <!-- Sample comments -->
                            ${generateComments()}
                        </div>
                    </div>
                </div>

                <style>
                    @keyframes slideInRight {
                        from {
                            transform: translateX(100%);
                        }
                        to {
                            transform: translateX(0);
                        }
                    }
                    
                    @keyframes pulse {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0.7; }
                    }
                    
                    @media (max-width: 768px) {
                        #videoPage [style*="grid-template-columns: 2fr 1fr"] {
                            grid-template-columns: 1fr !important;
                        }
                    }
                </style>
            `;
            
            // Add video page to body
            document.body.insertAdjacentHTML('beforeend', videoPageHTML);
        }

        // Close video page
        function closeVideoPage() {
            const videoPage = document.getElementById('videoPage');
            if (videoPage) {
                videoPage.style.animation = 'slideOutRight 0.5s ease-out';
                setTimeout(() => {
                    videoPage.remove();
                }, 500);
            }
        }

        // Start video (placeholder)
        function startVideo() {
            alert('سيتم تشغيل الفيديو الآن...\nهذا مجرد عرض تجريبي.');
        }

        // Generate video description
        function getVideoDescription(video) {
            const descriptions = {
                'movies': 'فيلم رائع يحكي قصة مشوقة مليئة بالأحداث المثيرة والمؤثرات البصرية المذهلة. تدور الأحداث حول شخصيات متنوعة تواجه تحديات كبيرة في عالم مليء بالمغامرات.',
                'series': 'مسلسل درامي متميز يتابع حياة مجموعة من الشخصيات المترابطة في قصة معقدة ومشوقة. كل حلقة تكشف المزيد من الأسرار والتطورات المثيرة.',
                'documentaries': 'وثائقي تعليمي يستكشف موضوعاً مهماً بطريقة شيقة ومفصلة، مع استخدام أحدث تقنيات التصوير والمونتاج لتقديم معلومات قيمة ومفيدة.',
                'anime': 'أنمي مثير يحكي قصة بطل شجاع يواجه قوى الشر في عالم خيالي مليء بالسحر والمغامرات. الرسوم المتحركة عالية الجودة والقصة آسرة.',
                'live': 'بث مباشر لأهم الأحداث والمباريات بجودة عالية وبدون انقطاع. استمتع بالمشاهدة المباشرة مع تعليقات الخبراء والتحليلات المفصلة.'
            };
            return descriptions[video.category] || 'محتوى ممتاز يستحق المشاهدة';
        }

        // Generate suggested videos
        function generateSuggestedVideos(currentVideoId) {
            const suggested = videos.filter(v => v.id !== currentVideoId).slice(0, 4);
            return suggested.map(video => `
                <div onclick="playVideo(${video.id})" style="
                    display: flex;
                    gap: 1rem;
                    background: rgba(255, 255, 255, 0.05);
                    border-radius: 15px;
                    padding: 1rem;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                " onmouseover="this.style.background='rgba(255, 255, 255, 0.1)'; this.style.transform='translateY(-2px)';"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.transform='translateY(0)';">
                    <div style="
                        width: 120px;
                        height: 80px;
                        background: ${video.gradient};
                        border-radius: 10px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                    ">
                        <div style="
                            width: 30px;
                            height: 30px;
                            background: rgba(255, 255, 255, 0.9);
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <span style="color: #333; font-size: 0.8rem; margin-left: 2px;">▶</span>
                        </div>
                    </div>
                    <div>
                        <h4 style="
                            color: white;
                            font-size: 0.9rem;
                            margin-bottom: 0.5rem;
                            line-height: 1.3;
                        ">${video.title}</h4>
                        <div style="
                            color: rgba(255, 255, 255, 0.6);
                            font-size: 0.8rem;
                        ">${video.views} • ${video.duration}</div>
                    </div>
                </div>
            `).join('');
        }

        // Generate sample comments
        function generateComments() {
            const comments = [
                { user: 'أحمد محمد', time: 'منذ ساعة', text: 'فيديو رائع! شكراً لك على هذا المحتوى المميز' },
                { user: 'فاطمة علي', time: 'منذ 3 ساعات', text: 'أعجبني كثيراً، أتطلع للمزيد من هذه النوعية' },
                { user: 'خالد سعد', time: 'منذ يوم', text: 'جودة عالية ومحتوى ممتاز، استمروا' }
            ];

            return comments.map(comment => `
                <div style="
                    display: flex;
                    gap: 1rem;
                    margin-bottom: 1.5rem;
                    padding-bottom: 1.5rem;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                ">
                    <div style="
                        width: 40px;
                        height: 40px;
                        background: linear-gradient(45deg, #45b7d1, #96c93d);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: bold;
                        color: white;
                        flex-shrink: 0;
                    ">${comment.user[0]}</div>
                    <div>
                        <div style="
                            display: flex;
                            gap: 1rem;
                            align-items: center;
                            margin-bottom: 0.5rem;
                        ">
                            <strong style="color: white;">${comment.user}</strong>
                            <span style="color: rgba(255, 255, 255, 0.6); font-size: 0.9rem;">${comment.time}</span>
                        </div>
                        <p style="
                            color: rgba(255, 255, 255, 0.8);
                            line-height: 1.5;
                            margin: 0;
                        ">${comment.text}</p>
                        <div style="
                            display: flex;
                            gap: 1rem;
                            margin-top: 0.5rem;
                        ">
                            <button style="
                                background: none;
                                border: none;
                                color: rgba(255, 255, 255, 0.6);
                                cursor: pointer;
                                font-size: 0.9rem;
                                transition: color 0.3s ease;
                            " onmouseover="this.style.color='#4ecdc4';" onmouseout="this.style.color='rgba(255, 255, 255, 0.6)';">
                                👍 إعجاب
                            </button>
                            <button style="
                                background: none;
                                border: none;
                                color: rgba(255, 255, 255, 0.6);
                                cursor: pointer;
                                font-size: 0.9rem;
                                transition: color 0.3s ease;
                            " onmouseover="this.style.color='#4ecdc4';" onmouseout="this.style.color='rgba(255, 255, 255, 0.6)';">
                                💬 رد
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Add slideOutRight animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                }
                to {
                    transform: translateX(100%);
                }
            }
        `;
        document.head.appendChild(style);

        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(0, 0, 0, 0.95)';
            } else {
                header.style.background = 'rgba(0, 0, 0, 0.9)';
            }
        });

        // Search functionality
        document.querySelector('.search-input').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filteredVideos = videos.filter(video => 
                video.title.toLowerCase().includes(searchTerm)
            );
            renderVideos(filteredVideos);
        });

        // Initialize the page
        document.addEventListener('DOMContentLoaded', () => {
            renderVideos();
            
            // Add some entrance animations
            setTimeout(() => {
                document.querySelectorAll('.video-card').forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(30px)';
                    setTimeout(() => {
                        card.style.transition = 'all 0.6s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            }, 500);
        });
