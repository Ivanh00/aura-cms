<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallConfigCommand extends Command
{
    public $description = 'Install Aura Configuration';

    public $signature = 'aura:install-config
                        {--teams= : Enable teams: true or false}
                        {--registration= : Allow public registration: true or false}';

    public function handle(): int
    {
        // Get the config path
        $configPath = config_path('aura.php');

        // Include the config array to read the current values
        $config = include $configPath;

        if (! is_array($config)) {
            $this->error('The published config/aura.php does not return an array.');

            return self::FAILURE;
        }

        // The published config is edited in place: every change is a targeted
        // replacement of a single value, so comments, env() calls and manual
        // edits survive untouched.
        $contents = file_get_contents($configPath);
        $original = $contents;

        // 1. Do you want to use teams?
        $currentTeams = (bool) ($config['teams'] ?? true);
        $useTeams = $this->input->isInteractive()
            ? confirm('Do you want to use teams?')
            : $this->booleanOption('teams', $currentTeams);

        $this->applySetting($contents, 'teams', $useTeams, $currentTeams);

        // 2. Do you want to modify default features?
        $modifyFeatures = $this->input->isInteractive()
            && confirm('Do you want to modify the default features?');

        if ($modifyFeatures) {
            // For each feature, ask if they want to enable/disable it
            foreach (($config['features'] ?? []) as $feature => $value) {
                if (! is_bool($value)) {
                    continue;
                }

                $this->applySetting($contents, "features.{$feature}", confirm("Enable feature '{$feature}'?", $value), $value);
            }
        }

        // 3. Do you want to allow registration?
        $currentRegistration = (bool) ($config['auth']['registration'] ?? true);
        $allowRegistration = $this->input->isInteractive()
            ? confirm('Do you want to allow registration?')
            : $this->booleanOption('registration', $currentRegistration);

        // Writes AURA_REGISTRATION when the config value is env-backed, and
        // rewrites the config line when it holds a literal.
        $this->applySetting($contents, 'auth.registration', $allowRegistration, $currentRegistration);

        // 4. Do you want to modify the default theme?
        $modifyTheme = $this->input->isInteractive()
            && confirm('Do you want to modify the default theme?');

        if ($modifyTheme) {
            foreach (($config['theme'] ?? []) as $option => $currentValue) {

                if (in_array($option, ['login-bg', 'login-bg-darkmode', 'app-favicon', 'app-favicon-darkmode', 'sidebar-darkmode-type'])) {
                    continue;
                }

                if ($option == 'color-palette') {
                    $choices = ['aura', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'mountain-meadow', 'sandal', 'slate', 'gray', 'zinc', 'neutral', 'stone'];
                    $newValue = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'gray-color-palette') {
                    $choices = ['slate', 'purple-slate', 'gray', 'zinc', 'neutral', 'stone', 'blue', 'smaragd', 'dark-slate', 'blackout'];
                    $newValue = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'darkmode-type') {
                    $choices = ['auto', 'light', 'dark'];
                    $newValue = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'sidebar-size') {
                    $choices = ['standard', 'compact', 'dense'];
                    $newValue = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'sidebar-type') {
                    $choices = ['primary', 'light', 'dark'];
                    $newValue = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'interface-density') {
                    $choices = ['comfortable', 'compact'];
                    $newValue = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'corner-radius') {
                    $choices = ['rounded', 'subtle', 'square'];
                    $newValue = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif (is_array($currentValue)) {
                    // Nested theme structures (colors, font, …) keep package defaults.
                    continue;
                } elseif (is_bool($currentValue)) {
                    // Boolean option
                    $newValue = confirm("Enable '{$option}'?", $currentValue);
                } elseif (is_scalar($currentValue)) {
                    // For other options, just ask for the value
                    $newValue = text(
                        label: "Enter value for '{$option}':",
                        default: (string) $currentValue
                    );
                } else {
                    continue;
                }

                $this->applySetting($contents, "theme.{$option}", $newValue, $currentValue);
            }
        }

        if ($contents !== $original) {
            file_put_contents($configPath, $contents);
        }

        $this->info('Aura configuration has been updated.');

        // Cache clear
        $this->info('Clearing cache...');
        if ($this->call('config:clear') !== self::SUCCESS) {
            return self::FAILURE;
        }

        return $this->call('cache:clear');
    }

    /**
     * Apply a single setting either to the .env file (when the config value is
     * env-backed) or by replacing exactly that value in the config file.
     */
    private function applySetting(string &$contents, string $path, bool|string|int|float $value, mixed $currentValue = null): void
    {
        $location = $this->locateValue($contents, $path);

        if ($location === null) {
            $this->warn("Could not find [{$path}] in config/aura.php. Skipping it, please update the value manually.");

            return;
        }

        $raw = substr($contents, $location['start'], $location['end'] - $location['start']);

        // Persist env()-backed values through .env so the published config
        // keeps working as documented.
        if (preg_match('/^env\(\s*[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]/', $raw, $matches)) {
            $this->setEnvValue($matches[1], $this->formatEnvValue($value));

        } elseif ($currentValue !== $value) {
            $contents = substr_replace($contents, var_export($value, true), $location['start'], $location['end'] - $location['start']);
        }

        // The installer continues in this process, so the selected value must
        // be visible before migrations or the first admin are created.
        config(["aura.{$path}" => $value]);
    }

    private function booleanOption(string $name, bool $default): bool
    {
        $value = $this->option($name);

        if ($value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => throw new \InvalidArgumentException("The --{$name} option must be true or false."),
        };
    }

    private function formatEnvValue(bool|string|int|float $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        if ($value === '' || preg_match('/[\s#"\']/', $value)) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }

    /**
     * Find the byte range of a dot-notated config value, e.g. `auth.registration`.
     *
     * @return array{start: int, end: int}|null
     */
    private function locateValue(string $contents, string $path): ?array
    {
        return $this->mapScalarValues($contents)[$path] ?? null;
    }

    /**
     * Map every non-array config value to its byte range in the file.
     *
     * The file is tokenized so brackets inside strings and comments cannot
     * confuse the lookup.
     *
     * @return array<string, array{start: int, end: int}>
     */
    private function mapScalarValues(string $contents): array
    {
        $offset = 0;
        $tokens = [];

        foreach (token_get_all($contents) as $token) {
            $text = is_array($token) ? $token[1] : $token;
            $id = is_array($token) ? $token[0] : null;

            if ($id === null || ! in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $tokens[] = ['id' => $id, 'text' => $text, 'start' => $offset, 'end' => $offset + strlen($text)];
            }

            $offset += strlen($text);
        }

        $map = [];
        $frames = [];
        $count = count($tokens);
        $i = 0;

        while ($i < $count) {
            $token = $tokens[$i];

            if ($token['id'] === T_CONSTANT_ENCAPSED_STRING && ($tokens[$i + 1]['id'] ?? null) === T_DOUBLE_ARROW && isset($tokens[$i + 2])) {
                $key = $this->unquote($token['text']);
                $value = $tokens[$i + 2];

                // Nested array: descend into it.
                if ($value['id'] === null && $value['text'] === '[') {
                    $frames[] = $key;
                    $i += 3;

                    continue;
                }

                $depth = 0;
                $j = $i + 2;
                $end = null;

                while ($j < $count) {
                    $current = $tokens[$j];

                    if ($current['id'] === null && in_array($current['text'], ['[', '('], true)) {
                        $depth++;
                    } elseif ($current['id'] === null && in_array($current['text'], [']', ')'], true)) {
                        if ($depth === 0) {
                            break;
                        }

                        $depth--;
                    } elseif ($current['id'] === null && $current['text'] === ',' && $depth === 0) {
                        break;
                    }

                    $end = $current['end'];
                    $j++;
                }

                $resolved = $this->resolvePath($frames, $key);

                if ($resolved !== null && $end !== null) {
                    $map[$resolved] = ['start' => $value['start'], 'end' => $end];
                }

                // Leave the terminator to the outer loop so `]` pops the frame.
                $i = $j;

                continue;
            }

            if ($token['id'] === null && $token['text'] === '[') {
                // The returned root array does not contribute to the path.
                $frames[] = ($tokens[$i - 1]['id'] ?? null) === T_RETURN ? null : '*';
                $i++;

                continue;
            }

            if ($token['id'] === null && $token['text'] === ']') {
                array_pop($frames);
                $i++;

                continue;
            }

            $i++;
        }

        return $map;
    }

    /**
     * Build the dot-notated path for a key, or null when it sits inside a
     * list-style array whose position we cannot address.
     *
     * @param  list<string|null>  $frames
     */
    private function resolvePath(array $frames, string $key): ?string
    {
        $parts = [];

        foreach ($frames as $frame) {
            if ($frame === null) {
                continue;
            }

            if ($frame === '*') {
                return null;
            }

            $parts[] = $frame;
        }

        $parts[] = $key;

        return implode('.', $parts);
    }

    private function setEnvValue(string $key, string $value): void
    {
        $envPath = app()->environmentFilePath();

        if (! file_exists($envPath)) {
            $this->warn("No .env file found, could not set {$key}={$value}.");

            return;
        }

        $env = file_get_contents($envPath);

        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
        $replacement = $key.'='.$value;

        if (preg_match($pattern, $env)) {
            // Replace the existing value, leaving every other line untouched.
            // Every occurrence is rewritten so a duplicated key cannot keep a stale value.
            $env = preg_replace($pattern, $replacement, $env);
        } else {
            $env = rtrim($env, "\r\n");
            $env = ($env === '' ? '' : $env.PHP_EOL).$replacement.PHP_EOL;
        }

        file_put_contents($envPath, $env);
    }

    private function unquote(string $token): string
    {
        $quote = $token[0] ?? "'";
        $inner = substr($token, 1, -1);

        if ($quote === "'") {
            return str_replace(['\\\\', "\\'"], ['\\', "'"], $inner);
        }

        return stripcslashes($inner);
    }
}
