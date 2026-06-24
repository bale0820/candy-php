WITH sample_posts(title, content, category, tags, author, replies, views) AS (
    VALUES
    (
        'Laravel API와 Vue 화면을 어떤 방식으로 나누면 좋을까요?',
        '인증은 Sanctum, 게시글은 REST API로 시작하려고 합니다. 폴더 구조와 라우팅 전략이 궁금합니다.',
        'Laravel',
        json_build_array('sanctum', 'api', 'architecture'),
        'backend-kim',
        12,
        438
    ),
    (
        'PostgreSQL 인덱스가 실제로 타는지 확인하는 습관',
        'EXPLAIN ANALYZE 결과를 볼 때 초보자가 놓치기 쉬운 부분들을 정리해봤습니다.',
        'Database',
        json_build_array('postgresql', 'index', 'performance'),
        'query-plan',
        7,
        291
    ),
    (
        'WSL2에서 Laravel 서버를 Windows 브라우저로 접속하기',
        '방화벽, CORS, Vite proxy까지 한 번에 맞추는 체크리스트를 공유합니다.',
        'DevOps',
        json_build_array('wsl2', 'firewall', 'cors'),
        'wsl-runner',
        18,
        611
    ),
    (
        'Vue 컴포넌트 상태 관리는 어디까지 ref로 충분할까요?',
        '작은 커뮤니티 프로젝트에서 Pinia를 바로 도입해야 하는지, composable로 버틸 수 있는지 이야기해보고 싶습니다.',
        'Vue',
        json_build_array('vue', 'state', 'composition-api'),
        'frontend-min',
        9,
        356
    ),
    (
        '주니어 개발자 포트폴리오에 커뮤니티 프로젝트를 넣는 법',
        'CRUD만 있는 프로젝트처럼 보이지 않게 기술 선택과 문제 해결 과정을 어떻게 보여주면 좋을까요?',
        'Career',
        json_build_array('portfolio', 'junior', 'career'),
        'career-dev',
        5,
        204
    ),
    (
        'Laravel 마이그레이션을 운영 DB에 적용할 때 체크할 것',
        '기존 테이블을 확장할 때 nullable/default/backfill 순서를 어떻게 잡는지 경험을 나눠주세요.',
        'Laravel',
        json_build_array('migration', 'postgresql', 'deploy'),
        'deploy-note',
        14,
        487
    )
)
INSERT INTO posts (title, content, category, tags, author, replies, views, created_at, updated_at)
SELECT title, content, category, tags, author, replies, views, now(), now()
FROM sample_posts sp
WHERE NOT EXISTS (
    SELECT 1 FROM posts p WHERE p.title = sp.title
);

SELECT id, title, category, author, replies, views
FROM posts
ORDER BY id;
