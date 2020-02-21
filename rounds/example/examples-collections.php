<?php

/**
 * Collections can be iterated as array in a foreach cycle
 */

require_once '../../bootstrap.php';

$coll = collect();

/** Insert */
for ($i = 0; $i < 1000; $i++)
    $coll->add(['a' => rand(0, 1000), 'b' => rand(0, 1000)]);

/** Map, Filter, Reject */
$coll = $coll->map(function ($v) {
    $v['a'] = $v['a'] * 2;
    return $v;
})->filter(function ($v, $key) { // whitelist
    return $v['a'] < 100;
})
    ->reject(function ($v) { // blacklist
        return $v['a'] > 50;
    });


/** Where
 * where('a', 10)
 * where('a', '>', 10)
 * whereIn('a', [10, 20])
 * whereNotIn('a', [10, 20])
 * whereBetween('a', [10, 20])
 */
$a = $coll->where('a', '>', 25);


/** Extract portions
 * first()
 * last()
 * take(100)
 */
$a = $coll->where('a', 10)->take(100);


/** Math
 * avg('a')
 * count()
 * min('a')
 * max('a')
 * median('a')
 */
$avg = $coll->avg('a');


/** Chunks */
$collection = collect([1, 2, 3, 4, 5, 6, 7]);
$chunks = $collection->chunk(4);
// [[1, 2, 3, 4], [5, 6, 7]]

/** Collapse */
$collapsed = $chunks->booksChunked->collapse();
// [1, 2, 3, 4, 5, 6, 7] //contrary o above

/** Pop - takes the last
 * shift() takes the first
 */
$collection = collect([1, 2, 3, 4, 5]);
$collection->pop();
// 5
$collection->all();
// [1, 2, 3, 4]


/** Splice (DOES mutate) */
$collection = collect([1, 2, 3, 4, 5]);
$chunk = $collection->splice(2);
$chunk->all();
// [3, 4, 5] from id 2 to N-1
$collection->all();
// [1, 2] the remaining


/** Slice (does NOT mutate) */
$collection = collect([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
$slice = $collection->slice(4, 2);
// [5, 6]


/** Sorting
 * sortBy('a')
 * sortByDesc('a')
 * reverse()
 */
$sorted = $collection->sortBy('price');


/** Forget */
$collection = collect(['name' => 'taylor', 'framework' => 'laravel']);
$collection->forget('name');
$collection->all();
// ['framework' => 'laravel']


/** Forget by key in array collection */
$coll = collect();
for ($i = 0; $i < 1000; $i++)
    $coll->add(['id' => $i, 'num' => $i]);
$coll = $coll->keyBy('id');
$coll = $coll->reverse();
$coll->forget(999); // does NOT delete by ID, but by insertion order


/**
 * KeyBy a given field
 */
$coll = $coll->keyBy('id');


/**
 * Reduce
 */
$booksChunkedScore = $collection->reduce(function ($carry, $books) {
    return $carry + $books->sum('award');
}, 0);


/**
 * Pluck attribute field into array
 */
$ids = $collection->pluck('id')->toArray();

