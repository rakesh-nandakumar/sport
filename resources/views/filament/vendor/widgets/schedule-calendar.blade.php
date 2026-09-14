<div>
    <x-filament::section icon="heroicon-o-calendar" heading="Schedule">
        <div
            wire:ignore
            x-data="{
                calendar: null,
                init() {
                    this.calendar = new FullCalendar.Calendar(this.$refs.calendar, {
                        initialView: window.innerWidth < 768 ? 'listWeek' : 'timeGridWeek',
                        headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay,listWeek' },
                        slotMinTime: '06:00:00',
                        slotMaxTime: '24:00:00',
                        height: 600,
                        nowIndicator: true,
                        events: (info, success, failure) => {
                            $wire.events(info.startStr, info.endStr).then(success).catch(failure);
                        },
                    });
                    this.calendar.render();
                },
            }"
        >
            <div x-ref="calendar"></div>
        </div>
    </x-filament::section>

    @once
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    @endonce
</div>
