@extends('layouts.app')

@section('title', 'チャンネル分析')

@section('content')
    <div class="container mt-4">
        
        <form id="analyzeForm">
            @csrf
            <input type="text" name="channel_id" placeholder="@ハンドル名を入力">
            <button type="submit">分析開始</button>
        </form>

        <div id="result" class="mt-3"></div>
    </div>

    <script>
        $(document).ready(function(){

            $('#analyzeForm').submit(function(e){
                e.preventDefault();

                const form = $(this);

                const formData = form.serialize();
                const result = $('#result');
                result.html('<p>分析中...</p>');

                $.ajax({
                    url: "{{ route('youtube.analyze')}}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token()}}'
                    },
                    data: formData,
                    success: function(response){
                        console.log(response);

                        if(response.errors) {

                            result.html(`<p style="color:red;">${response.errors.channel_id}</p>`);
                        }else if(response.status === 'success') {

                            if (!response.data) {
                                result.html(`<p style="color:red;">チャンネルが見つかりませんでした。</p>`);
                                return; // ここで処理を終了
                            }

                            const info = response.data.snippet;
                            const stats = response.data.statistics;

                            const items = response.videoes.items;


                            const labels = items.map((item, index) => `動画${index + 1}`);
                            const videoTitles = items.map(function(item){
                                return item.snippet.title;
                            });
                            const views = items.map(item => Number(item.statistics?.viewCount  ?? 0));
                            const likes = items.map(item => Number(item.statistics?.likeCount ?? 0));


                            let html =`
                             <h4>${info.title}</h4>
                             <p>${info.description}</p>
                             <p><strong>登録者数:</strong> ${formatNumberJapan(stats.subscriberCount)}</p>
                             <p><strong>動画数:</strong> ${formatNumberJapan(stats.videoCount)}</p>
                             <p><strong>総再生回数:</strong> ${formatNumberJapan(stats.viewCount)}</p>

                             <div class="row">
                                <div class="col-md-4">
                                    <img src="${info.thumbnails.default.url}" alt="thumbnail">
                                </div>
                            </div>

                            
                            <div class="col-md-5 mt-3 mb-4">
                                <h4 class="chart-title">最近の動画の長さ（直近50本）</h4>
                                <canvas id="circleChart"></canvas>
                            </div>
                                    
                            <div class="col-md-8 mt-8 mb-6">
                                <h4 class="chart-title">再生数・いいね数（上位10件）</h4>
                                <canvas id="myChart"></canvas>
                            </div>

                             <hr class="mb-4 mt-4">
                             <h5>最新動画一覧</h5>
                            `;

                            // 動画リスト一覧

                            html += `<table border="1" cellspacing="0" cellpadding="6">
                                    <tr>
                                        <th>No.</th>
                                        <th>タイトル</th>
                                        <th>公開日</th>
                                        <th>再生数</th>
                                        <th>高評価数</th>
                                        <th>コメント数</th>
                                        <th>長さ</th>
                                        <th>リンク</th>
                                    </tr>`;

                                    items.forEach((item, index) => {

                                        const statistics = item.statistics || {};

                                        let videoView = statistics.viewCount ??  '不明';
                                        const likeCount = statistics.likeCount ?? '不明'
                                        const commentCount = statistics.commentCount ?? '不明'
                                        const duration = item.contentDetails?.duration ?? null;
                                        

                                        html += 
                                        `<tr>
                                            <td>${index + 1}</td>
                                            <td>${truncateText(item.snippet.title)}</td>
                                            <td>${item.snippet.publishedAt}</td>
                                            <td>${formatNumberJapan(videoView)}</td>
                                            <td>${formatNumberJapan(likeCount)}</td>
                                            <td>${formatNumberJapan(commentCount)}</td>
                                            <td>${formatDuration(duration)}</td>
                                            <td><a href="https://www.youtube.com/watch?v=${item.id}" target="_blank">見る</a></td>
                                        </tr>`
                                    });

                            html +=`</table>`;

                            result.html(html);


                            //動画時間分布グラフ

                            
                            const durationStats = response.durationStats || {};


                            const durationLabels = Object.keys(durationStats);
                            
                            const durationCounts = Object.values(durationStats);

                            const circleChart = document.querySelector('#circleChart').getContext('2d');

                            new Chart(circleChart, {
                                type: 'pie',
                                data: {
                                    labels: durationLabels,
                                    datasets: [{
                                        data: durationCounts,
                                        backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF']
                                    }]
                                },
                                options: {
                                    responsive:true,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                        },

                                        tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                const value = context.raw || 0;
                                                const total = context.dataset.data.reduce((sum, v) => sum + v, 0);
                                                const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                                                const label = context.label || '';

                                                return `${label} : ${percent}% (${value}本)`;
                                            }
                                        }
                                    }
                                    },

                                }
                            })

                            //chart.jsグラフ
                            const ctx = document.querySelector('#myChart').getContext('2d');
                            new Chart(ctx, {
                                type:'bar',
                                data: {
                                    labels: labels,
                                    datasets: [
                                        {
                                            label:'再生数',
                                            data: views,
                                            yAxisID: 'yViews',
                                        },
                                        {
                                            label:'高評価数',
                                            data: likes,
                                            yAxisID: 'yLikes',
                                        },
                                    ]
                                },
                                options:{
                                    responsive:true,
                                    scales: {
                                        yViews:{
                                            type: 'linear',
                                            position: 'left',
                                            ticks: {
                                                callback: value => formatNumberJapan(value)
                                            }
                                        },
                                        yLikes: {
                                            type: 'linear',
                                            position: 'right',
                                            grid: {
                                                drawOnChartArea: false
                                            },
                                            ticks: {
                                                callback: value => formatNumberJapan(value)
                                            }
                                        }
                                    },
                                    plugins:{
                                        legend:{
                                            position: 'top',
                                        },
                                        
                                    tooltip:{
                                        callbacks: {
                                            title: function(tooltipItems){

                                                const index = tooltipItems[0].dataIndex;

                                                return videoTitles[index];
                                            }
                                        }
                                    },
                                    },


                                }
                            })


                        }else{
                            result.html(`<p style="color:red;">${response.message}</p>`)
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr);
                        result.html(`<p style="color:red;">通信エラーが発生しました。</p>`)
                    }
                })

            })

            function formatNumberJapan(num){
                num = Number(num);

                if(isNaN(num)) {
                    return '不明';
                }

                if(num >= 100000000) {
                    return (num / 100000000).toFixed(1).replace(/\.0$/, '') + '億';
                }

                if(num >= 10000) {
                    return (num / 10000).toFixed(1).replace(/\.0$/, '') + '万';
                }

                if(num >= 1000) {
                    return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
                }

                return num.toString();
            }

            function formatDuration(duration) {

                if(!duration || typeof duration !== 'string') {
                    return '不明';
                }

                const match = duration.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);

                
                if(!match) {
                    return '不明';
                }


                const hours = match[1]? Number(match[1]) : 0;
                const minutes = match[2]? Number(match[2]) : 0;
                const seconds = match[3]? Number(match[3]) : 0;



                //動画時間が1時間以上の時

                if(hours > 0) {
                    return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }

                return `${minutes}:${String(seconds).padStart(2,'0')}`;

            }

            function truncateText(text, maxLength = 30){
                
                if(!text) {
                    return '';
                }

                return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
            }
        });
    </script>
                   
@endsection