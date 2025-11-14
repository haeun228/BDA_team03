<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>식당 상세</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --accent:#ff7a18;
    --muted:#9aa0a6;
    font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  }
  body{
    margin:0;
    background:#ffffff;
    color:#111;
  }
  .wrap{
    max-width:980px;
    margin:60px auto;
    padding:0 36px;
  }

  .top-row{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:24px;
  }
  .meta{
    color:var(--muted);
    font-size:13px;
    margin-bottom:6px;
  }
  h1.title{
    margin:0;
    font-size:36px;
    line-height:1;
    font-weight:800;
  }
  .bookmark{
    width:44px;
    height:44px;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .desc{
    color:#333;
    margin-top:10px;
    font-size:15px;
    max-width:68%;
    line-height:1.5;
  }
  .location-row{
    margin-top:16px;
    color:var(--muted);
    font-size:14px;
    display:flex;
    align-items:center;
    gap:12px;
  }
  .menu-section{
    margin-top:18px;
  }
  .menu-section h3{
    margin:0 0 8px 0;
    font-size:18px;
  }
  .menu-list{
    list-style:disc;
    padding-left:20px;
    margin:0;
    color:#222;
  }
  .menu-list li{
    margin:6px 0;
    font-size:15px;
  }
  .reviews{
    margin-top:28px;
    display:flex;
    gap:40px;
    align-items:flex-start;
  }
  .overall{
    min-width:150px;
  }
  .rating-badge{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:10px;
  }
  .big-score{
    font-size:28px;
    font-weight:800;
  }
  .stars-inline{display:flex;gap:6px;align-items:center}
  .sub-ratings{
    display:flex;
    gap:28px;
    margin-top:8px;
  }
  .sub{
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    gap:6px;
  }
  .sub .label{font-size:13px;color:var(--muted)}
  .sub .val{display:flex;align-items:center;gap:8px;font-weight:700}
  .btn-wrap{
    margin-left:auto;
    display:flex;
    align-items:center;
  }
  button.primary{
    background:#f1f3f4;
    border:0;
    padding:10px 14px;
    border-radius:8px;
    font-weight:600;
    cursor:pointer;
  }
  .star{width:22px;height:22px;fill:var(--accent)}
  .small-star{width:16px;height:16px;fill:var(--accent)}
  @media (max-width:760px){
    .wrap{padding:20px;margin:20px}
    h1.title{font-size:26px}
    .desc{max-width:100%}
    .reviews{flex-direction:column;gap:16px}
    .sub-ratings{gap:18px}
    .top-row{flex-direction:column;align-items:flex-start}
  }
</style>
</head>
<body>
  <main class="wrap">
    <div class="top-row">
      <div style="flex:1;">
        <div class="meta">한식</div>
        <h1 class="title">식당이름</h1>
        <p class="desc">
          식당 설명 식당 설명 식당 설명 식당 설명 식당 설명 식당 설명 식당 설명
        </p>

        <div class="location-row">
          <div>서대문구</div>
          <div>·</div>
          <div style="font-weight:600">00:00 ~ 00:00</div>
        </div>

        <section class="menu-section">
          <h3>메뉴</h3>
          <ul class="menu-list">
            <li>○ ○ ○ ○ ○ : 10,000원</li>
            <li>○ ○ ○ ○ ○ : 10,000원</li>
            <li>○ ○ ○ ○ ○ : 10,000원</li>
          </ul>
        </section>

        <section style="margin-top:18px;">
          <div style="font-weight:700">리뷰 <span style="font-weight:500;color:var(--muted)">(16)</span></div>
        </section>
      </div>

      <div class="bookmark">
        <svg width="36" height="44" viewBox="0 0 36 44" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="4" y="2" width="28" height="38" rx="4" fill="var(--accent)"/>
          <path d="M18 28L13 31V6H23V31L18 28Z" fill="white" opacity="0.05"/>
        </svg>
      </div>
    </div>

    <div class="reviews">
      <div class="overall">
        <div class="rating-badge">
          <div class="big-score">5.0</div>
          <div class="stars-inline">
            <svg class="star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
            <svg class="star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
            <svg class="star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
            <svg class="star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
            <svg class="star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
          </div>
        </div>
      </div>

      <div style="flex:1;">
        <div class="sub-ratings">
          <div class="sub">
            <div class="label">친절도</div>
            <div class="val">
              <svg class="small-star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
              <span>5.0</span>
            </div>
          </div>
          <div class="sub">
            <div class="label">맛</div>
            <div class="val">
              <svg class="small-star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
              <span>5.0</span>
            </div>
          </div>
          <div class="sub">
            <div class="label">청결도</div>
            <div class="val">
              <svg class="small-star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
              <span>5.0</span>
            </div>
          </div>
        </div>
      </div>

      <div class="btn-wrap">
        <button class="primary">리뷰 등록</button>
      </div>
    </div>
  </main>
</body>
</html>