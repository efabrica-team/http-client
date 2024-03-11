<?php

namespace Efabrica\HttpClient;

enum OptionScope: int
{
    /**
     * All options are scoped to the base URI.
     */
    case SCOPE_ALL = 0xFFFFFF;

    /**
     * @default
     * Only headers and tokens are scoped to the base URI.
     */
    case SCOPE_HEADERS = 0b0011;

    /**
     * Only headers are scoped to the base URI.
     */
    case SCOPE_HEADERS_ONLY = 0b0010;

    /**
     * Only tokens are scoped to the base URI.
     */
    case SCOPE_TOKENS = 0b0001;

    /**
     * No options are scoped to the base URI.
     */
    case SCOPE_NONE = 0b0000;

    /**
     * @param array $options HttpClientInterface options
     * @return array Options that will be used only for the base URI.
     */
    public function filter(array $options): array
    {
        if ($this->value === self::SCOPE_ALL->value) {
            return $options;
        }
        $filtered = [];
        if ($this->value & self::SCOPE_HEADERS_ONLY->value) {
            $filtered['headers'] = $options['headers'] ?? [];
        }
        if ($this->value & self::SCOPE_TOKENS->value) {
            $filtered['bearer_token'] = $options['bearer_token'] ?? null;
            $filtered['auth_basic'] = $options['auth_basic'] ?? null;
        }
        return $filtered;
    }
}
