<?php
include("../config.php");
// sort 요청 들어오면 여기서 처리
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <title>Team03 Project</title>
    <link rel="stylesheet" href="../css/stats_style.css">
</head>
<body>
<div class="content-container">
    <h1>지역별 통계</h1>

    <div class="data-table-wrapper">
<!--        get 요청으로 테이블 안의 내용 가져와서 반복문으로 출력-->
        <table class="data-table">
            <thead>
            <tr>
                <th data-column="region_name">
                    지역명
<!--                    해당 정렬 기준을 담은 get 요청, default는 지역명 가나다 순-->
                    <a href="#" class="sort-button" data-sort="desc">
                        <span class="sort-icon">▼</span>
                    </a>
                </th>
                <th data-column="max_price">
                    최고 메뉴 가격
                    <a href="#" class="sort-button" data-sort="none">
                        <span class="sort-icon">▲▼</span>
                    </a>
                </th>
                <th data-column="min_price">
                    최소 메뉴 가격
                    <a href="#" class="sort-button" data-sort="none">
                        <span class="sort-icon">▲▼</span>
                    </a>
                </th>
                <th data-column="total_stores">
                    총 가게 수
                    <a href="#" class="sort-button" data-sort="none">
                        <span class="sort-icon">▲▼</span>
                    </a>
                </th>
                <th data-column="avg_rating">
                    평균 별점
                    <a href="#" class="sort-button" data-sort="none">
                        <span class="sort-icon">▲▼</span>
                    </a>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>마포</td>
                <td>1,000,000</td>
                <td>4,000</td>
                <td>235,232</td>
                <td>4.5</td>
            </tr>
            <tr>
                <td>tjdehdrn</td>
                <td>900,000</td>
                <td>4,000</td>
                <td>235,232</td>
                <td>4.5</td>
            </tr>
            <tr>
                <td>중구</td>
                <td>800,000</td>
                <td>4,000</td>
                <td>235,232</td>
                <td>4.5</td>
            </tr>
            <tr>
                <td>서대문구</td>
                <td>700,000</td>
                <td>4,000</td>
                <td>235,232</td>
                <td>4.5</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sortButtons = document.querySelectorAll('.sort-button');

        sortButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.preventDefault();

                const current = button.getAttribute('data-sort');
                const icon = button.querySelector('.sort-icon');

                // 버튼 초기화
                sortButtons.forEach(btn => {
                    if (btn !== button) {
                        btn.setAttribute('data-sort', 'none');
                        btn.querySelector('.sort-icon').innerHTML = '▲▼';
                    }
                });

                // 버튼 상태 변경
                // none 상태에서 누르면 내림차순이 되도록
                if (current === 'desc') {
                    button.setAttribute('data-sort', 'asc');
                    icon.innerHTML = '▲';
                } else if (current === 'none' ||current === 'asc') {
                    button.setAttribute('data-sort', 'desc');
                    icon.innerHTML = '▼';
                }
            });
        });
    });
</script>
</body>
</html>