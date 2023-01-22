<?php

class Solver {

    private string $game_type = '';
    private string $table_cards = '';
    private array $player_cards = [];
    private bool $correct_initial_data = true;
    private array $ranked_player_hands = [];
    private array $card_strength_ranking = ['A','K','Q','J','T','9','8','7','6','5','4','3','2'];

    private function pre_start_cleanup() {
        $this->game_type = '';
        $this->table_cards = '';
        $this->player_cards = [];
        $this->correct_initial_data = true;
        $this->ranked_player_hands = [];
    }

    public function process(string $line): string {
        $this->pre_start_cleanup();
        $this->validate_and_set_initial_data($line);
        if ($this->correct_initial_data === true) {
            $this->get_ranked_player_hands();
        }

        $sorted_player_hands = '';
        if ($this->ranked_player_hands !== []) {
            //$this->ranked_player_hands are ranked strongest to weakest, so here they are positioned correctly
            $counter = count($this->ranked_player_hands) - 1;
            while($counter > -1) {
                if ($counter === count($this->ranked_player_hands) - 1) {
                    $sorted_player_hands .= $this->ranked_player_hands[$counter][1];
                } else {
                    if ($this->ranked_player_hands[$counter][4] !== true) {
                        $sorted_player_hands .= ' ' . $this->ranked_player_hands[$counter][1];
                    } else {
                        $sorted_player_hands .= '=' . $this->ranked_player_hands[$counter][1];
                    }
                }
                $counter--;
            }
        }

//        print_r($this->ranked_player_hands);
//        echo $sorted_player_hands . "\n";
        return $sorted_player_hands;
    }

    private function validate_and_set_initial_data(string $line): void {
        if ($line) {
            $initial_data = explode(' ',$line);
            $number_of_initial_items = count($initial_data);
            $game_type = $initial_data[0];
            if ($game_type === 'texas-holdem' || $game_type === 'omaha-holdem') {
                $this->game_type = $game_type;
                if ($number_of_initial_items > 3 && strlen($initial_data[1]) === 10) {
                    $this->table_cards = $initial_data[1];

                    for ($counter = 2; $counter < $number_of_initial_items; $counter++) {
                        if (($this->game_type === 'texas-holdem' && strlen($initial_data[$counter]) === 4) || ($this->game_type === 'omaha-holdem' && strlen($initial_data[$counter]) === 8)) {
                            $this->player_cards[] = $initial_data[$counter];
                        } else {
                            $this->player_cards = [];
                            break;
                        }
                    }
                }
            } elseif ($game_type === 'five-card-draw') {
                $this->game_type = $game_type;
                if ($number_of_initial_items > 2) {
                    for ($counter = 1; $counter < $number_of_initial_items; $counter++) {
                        if (strlen($initial_data[$counter]) === 10) {
                            $this->player_cards[] = $initial_data[$counter];
                        } else {
                            $this->player_cards = [];
                            break;
                        }
                    }
                }
            }
        }

        if ($this->player_cards === []) {
            $this->correct_initial_data = false;
        }
    }

    public function get_ranked_player_hands():void {
        foreach ($this->player_cards as $player_combination) {
            $combined_cards_text = $player_combination;
            if ($this->game_type === 'texas-holdem' || $this->game_type === 'omaha-holdem') {
                $combined_cards_text .= $this->table_cards;
            }
            $combined_cards = [];
            $partially_split = str_split($combined_cards_text, 2);
            foreach ($partially_split as $item) {
                $combined_cards[] = str_split($item);
            }

            $five_card_combination = $this->check_if_has_straight_flush($combined_cards);
            if ($five_card_combination !== []) {
                $this->add_to_player_hand_combination_ranking($player_combination, $five_card_combination, 'straight_flush');
                continue;
            }
            $five_card_combination = $this->check_if_has_four_of_a_kind($combined_cards);
            if ($five_card_combination !== []) {
                $this->add_to_player_hand_combination_ranking($player_combination, $five_card_combination, 'four_of_a_kind');
                continue;
            }
            $five_card_combination = $this->check_if_has_full_house($combined_cards);
            if ($five_card_combination !== []) {
                $this->add_to_player_hand_combination_ranking($player_combination, $five_card_combination, 'full_house');
                continue;
            }
            $five_card_combination = $this->check_if_has_flush($combined_cards);
            if ($five_card_combination !== []) {
                $this->add_to_player_hand_combination_ranking($player_combination, $five_card_combination, 'flush');
                continue;
            }
            $five_card_combination = $this->check_if_has_straight($combined_cards);
            if ($five_card_combination !== []) {
                $this->add_to_player_hand_combination_ranking($player_combination, $five_card_combination, 'straight');
                continue;
            }
            $five_card_combination = $this->check_if_has_three_of_a_kind($combined_cards);
            if ($five_card_combination !== []) {
                $this->add_to_player_hand_combination_ranking($player_combination, $five_card_combination, 'three_of_a_kind');
                continue;
            }
            $five_card_combination = $this->check_if_has_two_pairs($combined_cards);
            if ($five_card_combination !== []) {
                $this->add_to_player_hand_combination_ranking($player_combination, $five_card_combination, 'two_pairs');
                continue;
            }
            $five_card_combination = $this->check_if_has_pair($combined_cards);
            if ($five_card_combination !== []) {
                $this->add_to_player_hand_combination_ranking($player_combination, $five_card_combination, 'pair');
                continue;
            }
            $five_card_combination = $this->get_high_card_combination($combined_cards);
            if ($five_card_combination !== []) {
                $this->add_to_player_hand_combination_ranking($player_combination, $five_card_combination, 'high_card');
            }
        }
    }

    private function add_to_player_hand_combination_ranking(string $initial_hand, array $best_combination, string $combination_type):void {
        $combination_ranking = [
            'straight_flush',
            'four_of_a_kind',
            'full_house',
            'flush',
            'straight',
            'three_of_a_kind',
            'two_pairs',
            'pair',
            'high_card'
        ];
        $number_of_ranked_hands = count($this->ranked_player_hands);
        if ($number_of_ranked_hands === 0) {
            $this->ranked_player_hands[] = [
                1 => $initial_hand,
                2 => $best_combination,
                3 => $combination_type,
                4 => false //is same strength as next combination
            ];
        } else {
            $counter = 0;
            $placement_complete = false;
            $new_hand_ranking = array_search($combination_type, $combination_ranking);
            $new_item = [
                1 => $initial_hand,
                2 => $best_combination,
                3 => $combination_type,
                4 => false
            ];
            do {
                $existing_hand_ranking = array_search($this->ranked_player_hands[$counter][3], $combination_ranking);
                if ($new_hand_ranking < $existing_hand_ranking) {
                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                    $placement_complete = true;
                } else if ($new_hand_ranking === $existing_hand_ranking) {
                    $new_first_card = $new_item[2][0][0];
                    $new_first_card_rank = array_search($new_first_card, $this->card_strength_ranking);
                    $existing_first_card = $this->ranked_player_hands[$counter][2][0][0];
                    $existing_first_card_rank = array_search($existing_first_card, $this->card_strength_ranking);
                    switch ($combination_type) {
                        case 'straight_flush':
                            if ($new_first_card_rank < $existing_first_card_rank) {
                                array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                $placement_complete = true;
                            } elseif ($new_first_card_rank === $existing_first_card_rank) {
                                $alphabetical_order = $this->determine_alphabetical_order($new_item[1], $this->ranked_player_hands[$counter][1]);
                                if ($alphabetical_order === 'first') {
                                    $new_item[4] = true;
                                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                    $placement_complete = true;
                                } else {
                                    $this->ranked_player_hands[$counter][4] = true;
                                }
                            }
                            break;
                        case 'four_of_a_kind':
                            if ($new_first_card_rank < $existing_first_card_rank) {
                                array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                $placement_complete = true;
                            } else if ($new_first_card_rank === $existing_first_card_rank) {
                                $alphabetical_order = $this->determine_alphabetical_order($new_item[1], $this->ranked_player_hands[$counter][1]);
                                if ($alphabetical_order === 'first') {
                                    $new_item[4] = true;
                                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                    $placement_complete = true;
                                } else {
                                    $this->ranked_player_hands[$counter][4] = true;
                                }
                            }
                            break;
                        case 'full_house':
                            if ($new_first_card_rank < $existing_first_card_rank) {
                                array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                $placement_complete = true;
                            } elseif ($new_first_card_rank === $existing_first_card_rank) {
                                $new_fourth_card = $new_item[2][3][0];
                                $new_fourth_card_rank = array_search($new_fourth_card, $this->card_strength_ranking);
                                $existing_fourth_card = $this->ranked_player_hands[$counter][2][3][0];
                                $existing_fourth_card_rank = array_search($existing_fourth_card, $this->card_strength_ranking);
                                if ($new_fourth_card_rank < $existing_fourth_card_rank) {
                                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                    $placement_complete = true;
                                } elseif ($new_fourth_card_rank === $existing_fourth_card_rank) {
                                    $alphabetical_order = $this->determine_alphabetical_order($new_item[1], $this->ranked_player_hands[$counter][1]);
                                    if ($alphabetical_order === 'first') {
                                        $new_item[4] = true;
                                        array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                        $placement_complete = true;
                                    } else {
                                        $this->ranked_player_hands[$counter][4] = true;
                                    }
                                }
                            }
                            break;
                        case 'flush':
                            $strength_comparison = $this->compare_kickers($new_item[2], $this->ranked_player_hands[$counter][2], 0);
                            if ($strength_comparison === 'first') {
                                array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                $placement_complete = true;
                            } elseif ($strength_comparison === 'equal') {
                                $alphabetical_order = $this->determine_alphabetical_order($new_item[1], $this->ranked_player_hands[$counter][1]);
                                if ($alphabetical_order === 'first') {
                                    $new_item[4] = true;
                                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                    $placement_complete = true;
                                } else {
                                    $this->ranked_player_hands[$counter][4] = true;
                                }
                            }
                            break;
                        case 'straight':
                            if ($new_first_card_rank < $existing_first_card_rank) {
                                array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                $placement_complete = true;
                            } elseif ($new_first_card_rank === $existing_first_card_rank) {
                                $alphabetical_order = $this->determine_alphabetical_order($new_item[1], $this->ranked_player_hands[$counter][1]);
                                if ($alphabetical_order === 'first') {
                                    $new_item[4] = true;
                                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                    $placement_complete = true;
                                } else {
                                    $this->ranked_player_hands[$counter][4] = true;
                                }
                            }
                            break;
                        case 'three_of_a_kind':
                            if ($new_first_card_rank < $existing_first_card_rank) {
                                array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                            } elseif ($new_first_card_rank === $existing_first_card_rank) {
                                $strength_comparison = $this->compare_kickers($new_item[2], $this->ranked_player_hands[$counter][2], 3);
                                if ($strength_comparison === 'first') {
                                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                    $placement_complete = true;
                                } elseif ($strength_comparison === 'equal') {
                                    $alphabetical_order = $this->determine_alphabetical_order($new_item[1], $this->ranked_player_hands[$counter][1]);
                                    if ($alphabetical_order === 'first') {
                                        $new_item[4] = true;
                                        array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                        $placement_complete = true;
                                    } else {
                                        $this->ranked_player_hands[$counter][4] = true;
                                    }
                                }
                            }
                            break;
                        case 'two_pairs':
                            if ($new_first_card_rank < $existing_first_card_rank) {
                                array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                $placement_complete = true;
                            } elseif ($new_first_card_rank === $existing_first_card_rank) {
                                $new_third_card = $new_item[2][2][0];
                                $new_third_card_rank = array_search($new_third_card, $this->card_strength_ranking);
                                $existing_third_card = $this->ranked_player_hands[$counter][2][2][0];
                                $existing_third_card_rank = array_search($existing_third_card, $this->card_strength_ranking);
                                if ($new_third_card_rank < $existing_third_card_rank) {
                                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                    $placement_complete = true;
                                } elseif ($new_third_card_rank === $existing_third_card_rank) {
                                    $strength_comparison = $this->compare_kickers($new_item[2], $this->ranked_player_hands[$counter][2], 4);
                                    if ($strength_comparison === 'first') {
                                        array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                        $placement_complete = true;
                                    } elseif ($strength_comparison === 'equal') {
                                        $alphabetical_order = $this->determine_alphabetical_order($new_item[1], $this->ranked_player_hands[$counter][1]);
                                        if ($alphabetical_order === 'first') {
                                            $new_item[4] = true;
                                            array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                            $placement_complete = true;
                                        } else {
                                            $this->ranked_player_hands[$counter][4] = true;
                                        }
                                    }
                                }
                            }
                            break;
                        case 'pair':
                            if ($new_first_card_rank < $existing_first_card_rank) {
                                array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                $placement_complete = true;
                            } elseif ($new_first_card_rank === $existing_first_card_rank) {
                                $strength_comparison = $this->compare_kickers($new_item[2], $this->ranked_player_hands[$counter][2], 2);
                                if ($strength_comparison === 'first') {
                                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                    $placement_complete = true;
                                } elseif ($strength_comparison === 'equal') {
                                    $alphabetical_order = $this->determine_alphabetical_order($new_item[1], $this->ranked_player_hands[$counter][1]);
                                    if ($alphabetical_order === 'first') {
                                        $new_item[4] = true;
                                        array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                        $placement_complete = true;
                                    } else {
                                        $this->ranked_player_hands[$counter][4] = true;
                                    }
                                }
                            }
                            break;
                        case 'high_card':
                            $strength_comparison = $this->compare_kickers($new_item[2], $this->ranked_player_hands[$counter][2], 0);
                            if ($strength_comparison === 'first') {
                                array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                $placement_complete = true;
                            } elseif ($strength_comparison === 'equal') {
                                $alphabetical_order = $this->determine_alphabetical_order($new_item[1], $this->ranked_player_hands[$counter][1]);
                                if ($alphabetical_order === 'first') {
                                    $new_item[4] = true;
                                    array_splice($this->ranked_player_hands, $counter, 0, [$new_item]);
                                    $placement_complete = true;
                                } else {
                                    $this->ranked_player_hands[$counter][4] = true;
                                }
                            }
                            break;
                    }
                }
                $counter++;
            } while ($placement_complete === false && $counter < $number_of_ranked_hands);

            if ($placement_complete === false) {
                $this->ranked_player_hands[] = $new_item;
            }
        }
    }

    private function check_if_has_straight_flush(array $combined_cards):array {
        $result = [];
        $suits = ['h','d','c','s'];
        $suited_cards = [];
        foreach($suits as $suit){
            foreach ($combined_cards as $card) {
                if ($card[1] === $suit) {
                    $suited_cards[] = $card;
                }
            }

            if (count($suited_cards) >= 5) {
                break;
            } else {
                $suited_cards = [];
            }
        }
        if (count($suited_cards) >= 5) {
            $suited_processed_cards = $this->check_if_has_straight($suited_cards);
            if ($suited_processed_cards !== []){
                $result = $suited_processed_cards;
            }
        }

        return $result;
    }

    private function check_if_has_four_of_a_kind(array $combined_cards):array {
        $result = [];
        $outer_counter = 0;
        $quads_found = false;
        $card_quantity = count($combined_cards);
        $first_card_index = -1;
        $second_card_index = -1;
        $third_card_index = -1;
        $fourth_card_index = -1;
        $quads_values = []; // in case there are two quads (omaha)
        $current_highest_quads_rank = 13;
        do {
            $temporary_second_card_index = -1;
            $temporary_third_card_index = -1;
            $outer_card_value = $combined_cards[$outer_counter][0];
            if (!in_array($outer_card_value, $quads_values)) {
                $inner_counter = $outer_counter + 1;
                while ($inner_counter < $card_quantity) {
                    $inner_card_value = $combined_cards[$inner_counter][0];
                    $inner_card_rank = array_search($inner_card_value, $this->card_strength_ranking);
                    if ($outer_card_value === $inner_card_value && $inner_card_rank < $current_highest_quads_rank) {
                        if ($temporary_second_card_index === -1) {
                            $temporary_second_card_index = $inner_counter;
                        } else if ($temporary_third_card_index === -1) {
                            $temporary_third_card_index = $inner_counter;
                        } else {
                            $quads_found = true;
                            $first_card_index = $outer_counter;
                            $second_card_index = $temporary_second_card_index;
                            $third_card_index = $temporary_third_card_index;
                            $fourth_card_index = $inner_counter;
                            if ($this->game_type === 'five-card-draw' || $this->game_type === 'texas-holdem') {
                                break 2;
                            } else {
                                $quads_values[] = $inner_card_value;
                                $current_highest_quads_rank = array_search($inner_card_value, $this->card_strength_ranking);
                            }
                        }
                    }
                    $inner_counter++;
                }
            }
            $outer_counter++;
        } while ($outer_counter < $card_quantity);
        if ($quads_found) {
            $quads = [];
            array_push($quads,
                $combined_cards[$first_card_index],
                $combined_cards[$second_card_index],
                $combined_cards[$third_card_index],
                $combined_cards[$fourth_card_index],
            );
            unset($combined_cards[$first_card_index]);
            unset($combined_cards[$second_card_index]);
            unset($combined_cards[$third_card_index]);
            unset($combined_cards[$fourth_card_index]);
            $kickers = $this->get_high_card_combination($combined_cards, 1);
            $result = array_merge($quads, $kickers);
        }

        return $result;
    }

    private function check_if_has_full_house(array $combined_cards):array {
        $result = [];
        $trips_combination = $this->check_if_has_three_of_a_kind($combined_cards);
        if ($trips_combination !== []) {
            $trips_value = $trips_combination[0][0];
            $highest_pair_rank = 13;
            $pair_indexes = [];
            $card_quantity = count($combined_cards);
            $outer_counter = 0;
            do {
                $outer_card = $combined_cards[$outer_counter][0];
                $outer_card_rank = array_search($outer_card, $this->card_strength_ranking);
                if ($outer_card !== $trips_value && $outer_card_rank < $highest_pair_rank) {
                    $inner_counter = $outer_counter + 1;
                    while ($inner_counter < $card_quantity) {
                        $inner_card = $combined_cards[$inner_counter][0];
                        if ($outer_card === $inner_card) {
                            $highest_pair_rank = array_search($inner_card, $this->card_strength_ranking);
                            $pair_indexes = [$outer_counter, $inner_counter];
                        }
                        $inner_counter++;
                    }
                }
                $outer_counter++;
            } while ($outer_counter < $card_quantity);
            if ($pair_indexes !== []) {
                unset($trips_combination[3]);
                unset($trips_combination[4]);
                $trips_combination[3] = $combined_cards[$pair_indexes[0]];
                $trips_combination[4] = $combined_cards[$pair_indexes[1]];
                $result = $trips_combination;
            }
        }

        return $result;
    }

    private function check_if_has_flush(array $combined_cards):array {
        $result = [];
        $suits = ['h','d','c','s'];
        $counter = 0;
        do {
            $suit = $suits[$counter];
            $flash_cards = $this->get_high_card_combination($combined_cards, 5, $suit);

            if (count($flash_cards) === 5) {
                $result = $flash_cards;
                break;
            }

            $counter++;
        } while ($counter < count($suits));

        return $result;
    }

    private function check_if_has_straight(array $combined_cards):array {
        $result = [];
        $checked_values = [];
        $straight_indexes = [];
        $card_quantity = count($combined_cards);
        $outer_counter = 0;
        do {
            $initial_card = $combined_cards[$outer_counter][0];
            if (!in_array($initial_card, $checked_values)) {
                $initial_rank_value = array_search($initial_card, $this->card_strength_ranking);
                $previous_rank = $initial_rank_value - 1;
                $next_rank = $initial_rank_value + 1;
                $checked_values[] = $initial_card;
                $straight_indexes[] = $outer_counter;

                $this->find_previous_or_next_straight_value($previous_rank, $next_rank, $checked_values,$straight_indexes, $combined_cards);
            }
            $straight_index_quantity = count($straight_indexes);
            if ($straight_index_quantity >= 5) {
                $unsorted_straight = [];
                foreach ($straight_indexes as $index) {
                    $unsorted_straight[] = $combined_cards[$index];
                }
                $result = $this->get_high_card_combination($unsorted_straight);
            } else {
                $straight_indexes = [];
            }
            $outer_counter++;
        } while($outer_counter < $card_quantity && $straight_index_quantity < 5);

        return $result;
    }

    private function check_if_has_three_of_a_kind(array $combined_cards):array {
        $result = [];
        $outer_counter = 0;
        $trips_found = false;
        $trips_values = []; // in case there are two (texas holdem) or three trips (omaha)
        $card_quantity = count($combined_cards);
        $first_card_index = -1;
        $second_card_index = -1;
        $third_card_index = -1;
        $current_highest_trips_rank = 13;

        do {
            $outer_card_value = $combined_cards[$outer_counter][0];
            $inner_counter = $outer_counter + 1;
            $temporary_second_index = -1;
            if (!in_array($outer_card_value, $trips_values)) {
                while ($inner_counter < $card_quantity) {
                    $inner_card_value = $combined_cards[$inner_counter][0];
                    $inner_card_rank = array_search($inner_card_value, $this->card_strength_ranking);
                    if ($outer_card_value === $inner_card_value && $inner_card_rank < $current_highest_trips_rank) {
                        if ($temporary_second_index === -1) {
                            $temporary_second_index = $inner_counter;
                        } else {
                            $trips_found = true;
                            $first_card_index = $outer_counter;
                            $second_card_index = $temporary_second_index;
                            $third_card_index = $inner_counter;
                            if ($this->game_type === 'five-card-draw') {
                                break 2;
                            } else {
                                $trips_values[] = $inner_card_value;
                                $current_highest_trips_rank = array_search($inner_card_value, $this->card_strength_ranking);
                            }
                        }
                    }
                    $inner_counter++;
                }
            }
            $outer_counter++;
        } while ($outer_counter < $card_quantity);
        if ($trips_found) {
            $trips = [];
            array_push($trips, $combined_cards[$first_card_index], $combined_cards[$second_card_index], $combined_cards[$third_card_index]);
            unset($combined_cards[$first_card_index]);
            unset($combined_cards[$second_card_index]);
            unset($combined_cards[$third_card_index]);
            $kickers = $this->get_high_card_combination($combined_cards, 2);
            $result = array_merge($trips, $kickers);
        }

        return $result;
    }

    private function check_if_has_two_pairs(array $combined_cards):array {
        $result = [];
        $outer_counter = 0;
        $pair_values = [];
        $pair_value_indexes = [];
        $potential_final_result = [];
        $card_quantity = count($combined_cards);
        do {
            $outer_card_value = $combined_cards[$outer_counter][0];
            if (!in_array($outer_card_value, $pair_values)) {
                $inner_counter = $outer_counter + 1;
                while ($inner_counter < $card_quantity) {
                    $inner_card_value = $combined_cards[$inner_counter][0];
                    if ($outer_card_value === $inner_card_value) {
                        if (count($pair_values) === 0) {
                            $potential_final_result[] = $combined_cards[$outer_counter];
                            $potential_final_result[] = $combined_cards[$inner_counter];
                            $pair_values[] = $combined_cards[$outer_counter][0];
                            array_push($pair_value_indexes, $outer_counter, $inner_counter);
                        } else if (count($pair_values) === 1) {
                            $first_pair_ranking = array_search($pair_values[0], $this->card_strength_ranking);
                            $new_pair_ranking =  array_search($outer_card_value, $this->card_strength_ranking);
                            if ($first_pair_ranking < $new_pair_ranking) {
                                $potential_final_result[] = $combined_cards[$outer_counter];
                                $potential_final_result[] = $combined_cards[$inner_counter];
                                $pair_values[] = $combined_cards[$outer_counter][0];
                                array_push($pair_value_indexes, $outer_counter, $inner_counter);
                            } else {
                                array_splice($potential_final_result, 0, 0, [$combined_cards[$inner_counter]]);
                                array_splice($potential_final_result, 0, 0, [$combined_cards[$outer_counter]]);
                                array_splice($pair_values, 0, 0, [$combined_cards[$outer_counter][0]]);
                                array_push($pair_value_indexes, $outer_counter, $inner_counter);
                            }
                        } else {
                            $first_pair_ranking = array_search($pair_values[0], $this->card_strength_ranking);
                            $second_pair_ranking =  array_search($pair_values[1], $this->card_strength_ranking);
                            $new_pair_ranking =  array_search($outer_card_value, $this->card_strength_ranking);
                            if ($first_pair_ranking > $new_pair_ranking) {
                                array_splice($potential_final_result, 0, 0, [$combined_cards[$inner_counter]]);
                                array_splice($potential_final_result, 0, 0, [$combined_cards[$outer_counter]]);
                                unset($potential_final_result[4]);
                                unset($potential_final_result[5]);
                                array_splice($pair_values, 0, 0, [$combined_cards[$outer_counter][0]]);
                                array_splice($pair_value_indexes, 0, 0, [$outer_counter]);
                                array_splice($pair_value_indexes, 0, 0, [$inner_counter]);
                                unset($pair_value_indexes[4]);
                                unset($pair_value_indexes[5]);
                            } else if ($second_pair_ranking > $new_pair_ranking) {
                                array_splice($potential_final_result, 2, 0, [$combined_cards[$inner_counter]]);
                                array_splice($potential_final_result, 2, 0, [$combined_cards[$outer_counter]]);
                                unset($potential_final_result[4]);
                                unset($potential_final_result[5]);
                                array_splice($pair_values, 1, 0, [$combined_cards[$outer_counter][0]]);
                                array_splice($pair_value_indexes, 2, 0, [$outer_counter]);
                                array_splice($pair_value_indexes, 2, 0, [$inner_counter]);
                                unset($pair_value_indexes[4]);
                                unset($pair_value_indexes[5]);
                            } else {
                                $pair_values[] = $combined_cards[$outer_counter][0];
                            }
                        }
                    }
                    $inner_counter++;
                }
            }
            $outer_counter++;
        } while ($outer_counter < $card_quantity);

        if (count($pair_values) >= 2) {
            foreach ($pair_value_indexes as $index) {
                unset($combined_cards[$index]);
            }
            $kicker_card = $this->get_high_card_combination($combined_cards, 1);
            $result = array_merge($potential_final_result, $kicker_card);
        }

        return $result;
    }

    private function check_if_has_pair(array $combined_cards):array {
        $result = [];
        $outer_counter = 0;
        $pair_found = false;
        $card_quantity = count($combined_cards);
        do {
            $outer_card_value = $combined_cards[$outer_counter][0];
            $inner_counter = $outer_counter + 1;
            while ($inner_counter < $card_quantity) {
                $inner_card_value = $combined_cards[$inner_counter][0];
                if ($outer_card_value === $inner_card_value) {
                    $pair_found = true;
                    break 2;
                }
                $inner_counter++;
            }
            $outer_counter++;
        } while ($outer_counter < $card_quantity);
        if ($pair_found) {
            $pairs = [];
            array_push($pairs, $combined_cards[$outer_counter], $combined_cards[$inner_counter]);
            unset($combined_cards[$outer_counter]);
            unset($combined_cards[$inner_counter]);
            $kickers = $this->get_high_card_combination($combined_cards, 3);
            $result = array_merge($pairs, $kickers);
        }

        return $result;
    }

    private function get_high_card_combination(array $combined_cards, int $combination_length = 5, string $suit_value = ''):array {
        $result = [];
        foreach ($combined_cards as $card) {
            if ($suit_value === '' || $suit_value === $card[1]) {
                $current_result_length = count($result);
                if ($current_result_length === 0) {
                    $result[] = $card;
                } else {
                    $counter = 0;
                    $added_new = false;
                    $current_card_ranking = array_search($card[0], $this->card_strength_ranking);
                    do {
                        $result_card_ranking = array_search($result[$counter][0], $this->card_strength_ranking);
                        if ($current_card_ranking < $result_card_ranking) {
                            array_splice($result, $counter, 0, [$card]);
                            $added_new = true;
                            break;
                        }
                        $counter++;
                    } while ($counter < $current_result_length);
                    if (!$added_new && $current_result_length < $combination_length) {
                        $result[] = $card;
                    } else if ($added_new && ($current_result_length + 1) > $combination_length) {
                        unset($result[$combination_length]);
                    }
                }
            }
        }

        return $result;
    }

    private function find_previous_or_next_straight_value(int $previous_rank,int $next_rank,array &$checked_values,array &$straight_indexes,array $combined_cards):void {
        $counter = 0;
        $card_quantity = count($combined_cards);
        $found_at_least_one = false;
        do {
            $card = $combined_cards[$counter];
            if (!in_array($card[0], $checked_values)) {
                $current_card_rank = array_search($card[0], $this->card_strength_ranking);
                if ($current_card_rank === $previous_rank || $current_card_rank === $next_rank) {
                    $checked_values[] = $card[0];
                    $straight_indexes[] = $counter;
                    $found_at_least_one = true;
                    if ($current_card_rank === $previous_rank) {
                        $previous_rank--;
                    } else {
                        $next_rank++;
                    }
                }
            }
            $counter++;
        } while ($counter < $card_quantity);

        if ($found_at_least_one) {
            $this->find_previous_or_next_straight_value($previous_rank, $next_rank, $checked_values, $straight_indexes, $combined_cards);
        }
    }

    private function compare_kickers(array $first_hand, array $second_hand, int $starting_card):string {
        $counter = $starting_card;

        while ($counter < 5) {
            $first_hand_card_ranking = array_search($first_hand[$counter][0], $this->card_strength_ranking);
            $second_hand_card_ranking = array_search($second_hand[$counter][0], $this->card_strength_ranking);
            if ($first_hand_card_ranking < $second_hand_card_ranking) {
                return 'first';
            } elseif ($first_hand_card_ranking > $second_hand_card_ranking) {
                return 'second';
            }
            $counter++;
        }

        return 'equal';
    }

    private function determine_alphabetical_order(string $first_hand_text, string $second_hand_text):string {
        $suit_order = ['c','d','h','s'];
        $first_hand = str_split($first_hand_text, 2);
        $second_hand = str_split($second_hand_text, 2);
        $first_hand_card_value_rank = array_search($first_hand[0][0], $this->card_strength_ranking);
        $second_hand_card_value_rank = array_search($second_hand[0][0], $this->card_strength_ranking);
        $first_hand_suit_rank = array_search($first_hand[0][1], $suit_order);
        $second_hand_suit_rank = array_search($second_hand[0][1], $suit_order);

        if ($first_hand_suit_rank > $second_hand_suit_rank) {
            return 'first';
        } elseif ($first_hand_suit_rank < $second_hand_suit_rank) {
            return 'second';
        } else {
            if ($first_hand_card_value_rank > $second_hand_card_value_rank) {
                return 'first';
            } else { //two identical cards shouldn't exist, so there is no elseif check
                return 'second';
            }
        }
    }
}
