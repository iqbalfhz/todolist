<?php

use App\Rules\Position;
use Illuminate\Support\Facades\Validator;

it('accepts a position the database can store and refuses one too far from zero', function (mixed $position, bool $passes) {
    $validator = Validator::make(['position' => $position], ['position' => ['numeric', new Position]]);

    expect($validator->passes())->toBe($passes);
})->with([
    'a whole number' => [1024, true],
    'a fraction' => [1024.5, true],
    'a negative number, as for a new cover photo' => [-2048, true],
    'the limit' => ['1e15', true],
    'the negative limit' => ['-1e15', true],
    'just past the limit' => ['1.0000000000000001e15', false],
    'past the negative limit' => ['-2e15', false],
    'a number too large for a double' => ['1e400', false],
    'infinity that arrived as a JSON number' => [INF, false],
]);

it('leaves a value that is not a number to the numeric rule', function () {
    $validator = Validator::make(['position' => 'atas'], ['position' => ['numeric', new Position]]);

    expect($validator->errors()->get('position'))->toHaveCount(1);
});
