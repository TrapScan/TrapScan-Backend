@component('vendor.mail.html.message')
# *{{ $user->name }}* reprted an issue with trap:
**TrapNZ ID:** *{{ $trap->nz_trap_id }}*<br>
**QR ID:** *{{ $trap->qr_id }}*<br>
**TrapNZ Name:** *{{ $trap->name  }}*

## The issue they reported is:<br>
@component('vendor.mail.html.panel')
## *{{ $inspection->words }}*
@endcomponent

@if($inspection->notes && $inspection->notes !== 'test')
## They wrote the following about the issue:
@component('vendor.mail.html.panel')
{{ $inspection->notes }}
@endcomponent
@else
## They didn't provide any further information
@endif

Their email address is {{ $inspection->user->email }}
<br>

Thanks,<br>
{{ config('app.name') }}
@endcomponent
