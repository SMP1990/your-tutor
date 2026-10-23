{{--
    Structured data for search engines (JSON-LD). Usage:
    @include('partials.schema', ['schema' => [...]])   inside @push('schema')
--}}
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
