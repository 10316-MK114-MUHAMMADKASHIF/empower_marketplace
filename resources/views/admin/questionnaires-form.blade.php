<x-layouts.app title="{{ isset($questionnaire) ? 'Edit Questionnaire' : 'New Questionnaire' }}">
    @include('admin._nav', ['active' => 'questionnaires'])
    <livewire:admin.questionnaire-form :questionnaire="$questionnaire ?? null" />
</x-layouts.app>
