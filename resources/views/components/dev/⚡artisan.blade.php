<?php

use Livewire\Component;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

new class extends Component {
    public $command = '';
    public $commands = [];
    public $output = [];
    public $isRunning = false;
    public $currentDirectory = '';
    public $terminalPrompt = 'artisan@laravel:~$';

    protected $listeners = ['executeCommand', 'clearTerminal'];

    public function mount()
    {
        $this->currentDirectory = base_path();
        $this->addOutputLine("Terminal Ready. Type 'php artisan' commands below.");
        $this->addOutputLine("Type 'clear' to clear the terminal or 'exit' to go back.");
        $this->addOutputLine('--------------------------------------------------------');
    }

    public function executeCommand($cmd = null)
    {
        $commandToRun = $cmd ?: $this->command;

        if (empty(trim($commandToRun))) {
            return;
        }

        if (trim($commandToRun) === 'clear') {
            $this->commands[] = 'clear';
            $this->clearTerminal();
            $this->command = '';
            return;
        }

        if (trim($commandToRun) === 'exit') {
            $this->addOutputLine('Session ended.');
            $this->command = '';
            return;
        }

        $this->isRunning = true;
        $this->addOutputLine("{$this->terminalPrompt} {$commandToRun}");

        try {
            if (!str_starts_with($command, 'php artisan')) {
                throw new \Exception('Only Artisan commands are allowed.');
            }

            $this->addOutputLine($result);
        } catch (\Exception $e) {
            $this->addOutputLine('Error: ' . $e->getMessage());
        }

        $this->isRunning = false;
        $this->commands[] = $this->command;
        $this->command = '';
    }

    private function runArtisanCommand($command)
    {
        $sanitizedCommand = str_replace('php artisan', '', $command);
        $sanitizedCommand = trim($sanitizedCommand);

        // For security, you might want to whitelist allowed commands
        // $allowedCommands = ['make:model', 'make:controller', 'make:migration', 'migrate', 'migrate:status', 'db:seed', 'route:list', 'cache:clear', 'config:clear', 'view:clear', 'queue:work', 'schedule:run', 'tinker', '--version', 'list', 'make:livewire', 'make:component', 'storage:link'];

        $commandParts = explode(' ', $sanitizedCommand);
        $baseCommand = $commandParts[0];

        // Check if command is allowed (for security)
        // if (!in_array($baseCommand, $allowedCommands) && $baseCommand !== '') {
        //     return "Command '{$baseCommand}' is not allowed for security reasons.";
        // }

        // Execute the artisan command
        $process = new Process(['php', 'artisan', ...explode(' ', $sanitizedCommand)], base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    private function runShellCommand($command)
    {
        // Only allow basic shell commands for security
        // $allowedShellCommands = ['ls', 'pwd', 'whoami', 'date', 'php'];

        // $commandParts = explode(' ', trim($command));
        // $baseCommand = $commandParts[0];

        // if (!in_array($baseCommand, $allowedShellCommands)) {
        //     return "Shell command '{$baseCommand}' is not allowed for security reasons.";
        // }

        // // Specifically handle php commands
        // if ($baseCommand === 'php') {
        //     // Allow only certain php commands for security
        //     $allowedPhpCommands = ['-v', '--version', '-m'];

        //     if (isset($commandParts[1]) && !in_array($commandParts[1], $allowedPhpCommands)) {
        //         return "PHP option '{$commandParts[1]}' is not allowed for security reasons.";
        //     }
        // }

        $process = Process::fromShellCommandline($command, base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function clearTerminal()
    {
        $this->output = [];
    }

    private function addOutputLine($line)
    {
        $lines = explode("\n", $line);
        foreach ($lines as $singleLine) {
            if (!empty(trim($singleLine)) || $singleLine === '') {
                $this->output[] = $singleLine;
            }
        }

        // Keep only last 100 lines to prevent memory issues
        if (count($this->output) > 100) {
            $this->output = array_slice($this->output, -100);
        }
    }

    public function updatedCommand()
    {
        // Auto-execute when Enter is pressed (handled in JavaScript)
    }
}; ?>
<div {{ $attributes->merge(['class' => 'terminal-container p-4 font-mono', 'wire:ignore.self']) }}
    x-data="{
        historyIndex: -1,
        init() {
            // Initialize after Livewire loads
            this.$watch('$wire.commands.length', (value) => {
                // Reset index when new commands are added
                this.historyIndex = value;
            });
        },
        handleKeyDown(event) {
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.handleArrowUp();
            }
    
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.handleArrowDown();
            }
        },
        handleArrowUp() {
            const commands = this.$wire.commands;
    
            if (this.historyIndex < 0) {
                // Start from the last command
                this.historyIndex = commands.length - 1;
            } else if (this.historyIndex > 0) {
                // Go to previous command
                this.historyIndex--;
            }
    
            if (this.historyIndex >= 0 && commands[this.historyIndex]) {
                this.$wire.set('command', commands[this.historyIndex]);
            }
        },
        handleArrowDown() {
            const commands = this.$wire.commands;
    
            if (this.historyIndex < commands.length - 1) {
                this.historyIndex++;
                if (commands[this.historyIndex]) {
                    this.$wire.set('command', commands[this.historyIndex]);
                }
            } else if (this.historyIndex >= commands.length - 1) {
                // Clear input when at the end
                this.historyIndex = commands.length;
                this.$wire.set('command', '');
            }
        }
    }">

    <div id="terminal-output" class=" overflow-y-auto " x-data x-init="$el.scrollTop = $el.scrollHeight">
        @foreach ($output as $line)
            <div class=" break-words {{ $loop->last ? 'text-green-300' : 'text-green-400' }}">

                {{ $line }}
            </div>
        @endforeach

        @if ($isRunning)
            <div class="text-yellow-400">
                {{ $terminalPrompt }} {{ $command }}
                <span class="animate-pulse">▊</span>
            </div>
        @endif
    </div>

    <div class="flex items-center">
        <span class="text-yellow-400 mr-2">{{ $terminalPrompt }}</span>
        <input type="text" wire:model="command" wire:keydown.enter="executeCommand" x-data x-ref="input"
            x-on:keydown.enter="$nextTick(() => { $refs.input.value = ''; })"
            class="flex-grow bg-transparent text-green-400 outline-none font-mono w-full" autocomplete="off"
            spellcheck="false" {{ $isRunning ? 'disabled' : '' }} @keydown="handleKeyDown" />
        @if ($isRunning)
            <div class="ml-2">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-400"></div>
            </div>
        @endif
    </div>


</div>


<script>
    const terminalOutput = document.getElementById('terminal-output');

    Livewire.hook('morph.added', ({
        el,
        component
    }) => {
        Livewire.dispatch('terminal-command-processed')
    })
    // Focus input field when component loads
    const inputField = document.querySelector('input[wire\\:model="command"]');
    if (inputField) {
        inputField.focus();
    }
</script>
