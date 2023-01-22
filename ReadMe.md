## Documentation

### How to build and run

All guidelines were also followed, only Solver.php was edited and this ReadMe file added,
nothing else was changed that could impact how this program should be run.

### Program limitations and known defects

There is one limitation - partial data validation. In the start I checked that string structure is correct for game type,
but I didn't check that table cards or player cards are unique (one card doesn't exist in multiple places) and that all card
(table and player) are correct (from either h, d, c, s suit or A, K, Q, J, T, 9, 8, 7, 6, 5, 4, 3, 2 rank), so either reappearing
card or incorrect card value could affect program in an unforeseen way.

As far as I know there should be no other limitations or defects, program should work as intended, on all three poker game types.
It's possible that some edge cases slipped through, only further testing will reveal those. All edge cases 
I could think of, I covered.

### Additional notes

In README.md it's mentioned "In case there are multiple hands with the same value on the same board they should be ordered alphabetically and separated by = signs",
but there are few special cases, for example "texas-holdem AcAsKhKsKd 9d4d 8d3d", in this case hands are equal, but there is no possibility for
alphabetical ordering.
In all cases ordering will be decided based on first card, if their rank is equal then by alphabetical order of suit, otherwise 
first priority is still with suit alphabetical order, but if it's same suit then position is decided based on first cards rank(higher rank first). 

function add_to_player_hand_combination_ranking is a bit messy, was stuck between leaving as is or changing the code(so there would be less code duplicates),
but it seemed to complicate structure and readability, so in the end decided to leave as is. 

It wasn't specified, if it's casino poker, so minimal amount of players is two(player vs player).
