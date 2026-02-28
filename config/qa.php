<?php

return [

    'presets' => [

        'left_chest' => [
            'label' => 'Left Chest',
            'hoop_limits_mm' => ['width' => 100, 'height' => 100],
            'rules' => [
                'max_jump_count_warn' => 200,
                'max_jump_count_fail' => 400,
                'max_longest_jump_mm_warn' => 10.0,
                'max_longest_jump_mm_fail' => 15.0,

                'max_color_changes_warn' => 12,
                'max_color_changes_fail' => 20,

                'min_stitch_length_mm_warn' => 0.4,
                'min_stitch_length_mm_fail' => 0.3,

                'density_tile_size_mm' => 4.0,
                'density_hotspot_threshold_warn' => 0.18,
                'density_hotspot_threshold_fail' => 0.25,
                'density_hotspot_tiles_warn' => 25,
                'density_hotspot_tiles_fail' => 60,

                'tiny_text_min_design_height_mm' => 20,
                'tiny_text_risk_density_threshold' => 0.18,
            ],
        ],

        'cap' => [
            'label' => 'Cap',
            'hoop_limits_mm' => ['width' => 130, 'height' => 60],
            'rules' => [
                'max_jump_count_warn' => 150,
                'max_jump_count_fail' => 300,
                'max_longest_jump_mm_warn' => 8.0,
                'max_longest_jump_mm_fail' => 12.0,

                'max_color_changes_warn' => 10,
                'max_color_changes_fail' => 16,

                'min_stitch_length_mm_warn' => 0.45,
                'min_stitch_length_mm_fail' => 0.35,

                'density_tile_size_mm' => 4.0,
                'density_hotspot_threshold_warn' => 0.16,
                'density_hotspot_threshold_fail' => 0.22,
                'density_hotspot_tiles_warn' => 20,
                'density_hotspot_tiles_fail' => 45,

                'tiny_text_min_design_height_mm' => 18,
                'tiny_text_risk_density_threshold' => 0.16,
            ],
        ],

        'patch' => [
            'label' => 'Patch',
            'hoop_limits_mm' => ['width' => 120, 'height' => 120],
            'rules' => [
                'max_jump_count_warn' => 250,
                'max_jump_count_fail' => 500,
                'max_longest_jump_mm_warn' => 12.0,
                'max_longest_jump_mm_fail' => 18.0,

                'max_color_changes_warn' => 15,
                'max_color_changes_fail' => 25,

                'min_stitch_length_mm_warn' => 0.4,
                'min_stitch_length_mm_fail' => 0.3,

                'density_tile_size_mm' => 4.0,
                'density_hotspot_threshold_warn' => 0.22,
                'density_hotspot_threshold_fail' => 0.30,
                'density_hotspot_tiles_warn' => 35,
                'density_hotspot_tiles_fail' => 80,

                'tiny_text_min_design_height_mm' => 22,
                'tiny_text_risk_density_threshold' => 0.22,
            ],
        ],

        'custom' => [
            'label' => 'Custom',
            'hoop_limits_mm' => ['width' => 200, 'height' => 200],
            'rules' => [
                'max_jump_count_warn' => 250,
                'max_jump_count_fail' => 500,
                'max_longest_jump_mm_warn' => 12.0,
                'max_longest_jump_mm_fail' => 18.0,
                'max_color_changes_warn' => 15,
                'max_color_changes_fail' => 25,
                'min_stitch_length_mm_warn' => 0.4,
                'min_stitch_length_mm_fail' => 0.3,

                'density_tile_size_mm' => 4.0,
                'density_hotspot_threshold_warn' => 0.20,
                'density_hotspot_threshold_fail' => 0.28,
                'density_hotspot_tiles_warn' => 30,
                'density_hotspot_tiles_fail' => 70,

                'tiny_text_min_design_height_mm' => 20,
                'tiny_text_risk_density_threshold' => 0.20,
            ],
        ],
    ],

    'severity_weights' => [
        'pass' => 0,
        'warn' => 8,
        'fail' => 20,
    ],

    'scoring' => [
        'base_score' => 100,
        'min_score' => 0,
        'max_score' => 100,
    ],

];
