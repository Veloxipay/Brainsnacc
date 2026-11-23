"""Make topic videos for every teaching script that passes the checker and has no up-to-date video.

    python3 video/render_teach_all.py [--minutes 9] [--limit N]

Stops starting new videos after --minutes, so it can be run again and again; finished videos are skipped.
"""
import glob, os, subprocess, sys, time

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
sys.path.insert(0, HERE)
import board_video, check_teach

def arg(name, default):
    return float(sys.argv[sys.argv.index(name) + 1]) if name in sys.argv else default

def main():
    stop_at = time.time() + arg('--minutes', 9) * 60
    limit = int(arg('--limit', 10 ** 6))
    known = check_teach.lessons()
    done = 0
    # Years 7–10 Maths first (the main product), then selective prep, then the sciences.
    order = ['year-7/maths', 'year-8/maths', 'year-9/maths', 'year-10/maths', 'year-7/selective']
    def rank(p):
        rel = os.path.relpath(p, os.path.join(ROOT, 'database', 'content', 'teach'))
        key = '/'.join(rel.split(os.sep)[:2])
        return (order.index(key) if key in order else len(order), rel)
    for path in sorted(glob.glob(os.path.join(ROOT, 'database', 'content', 'teach', '*', '*', '*.json')), key=rank):
        if time.time() > stop_at or done >= limit:
            break
        rel = os.path.relpath(path, os.path.join(ROOT, 'database', 'content', 'teach'))
        out = os.path.join(ROOT, 'public', 'videos', 'teach', os.path.splitext(rel)[0] + '.mp4')
        stamp = os.path.join(HERE, 'stamps', os.path.splitext(rel)[0] + '.hash')
        if os.path.exists(out) and os.path.exists(stamp):
            continue  # made already (board_video re-checks the hash if run directly)
        if check_teach.check(path, known):
            print('skip (checker):', rel); continue
        try:
            board_video.render(path)
            done += 1
        except Exception as e:  # one bad script must not stop the rest
            print('FAILED', rel, e)
    left = sum(1 for p in glob.glob(os.path.join(ROOT, 'database', 'content', 'teach', '*', '*', '*.json'))
               if not os.path.exists(os.path.join(ROOT, 'public', 'videos', 'teach', os.path.splitext(os.path.relpath(p, os.path.join(ROOT, 'database', 'content', 'teach')))[0] + '.mp4')))
    print(f'made {done}; scripts still without a video: {left}')

if __name__ == '__main__':
    main()
