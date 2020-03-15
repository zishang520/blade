<?php
namespace luoyy\Blade\Compilers;

use InvalidArgumentException;
use luoyy\Blade\AnonymousComponent;
use luoyy\Blade\Support\Collection;
use luoyy\Blade\Support\Str;
use ReflectionClass;

/**
 * @author Spatie bvba <info@spatie.be>
 * @author Taylor Otwell <taylor@laravel.com>
 */
class ComponentTagCompiler
{
    /**
     * The component class aliases.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Create new component tag compiler.
     *
     * @param  array  $aliases
     * @return void
     */
    public function __construct(array $aliases = [])
    {
        $this->aliases = $aliases;
    }

    /**
     * Compile the component and slot tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    public function compile(string $value)
    {
        $value = $this->compileSlots($value);

        return $this->compileTags($value);
    }

    /**
     * Compile the tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    public function compileTags(string $value)
    {
        $value = $this->compileSelfClosingTags($value);
        $value = $this->compileOpeningTags($value);
        $value = $this->compileClosingTags($value);

        return $value;
    }

    /**
     * Compile the opening tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileOpeningTags(string $value)
    {
        $pattern = "/
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                (?<attributes>
                    (?:
                        \s+
                        [\w\-:.@]+
                        (
                            =
                            (?:
                                \\\"[^\\\"]*\\\"
                                |
                                \'[^\']*\'
                                |
                                [^\'\\\"=<>]+
                            )
                        )
                    ?)*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

        return preg_replace_callback($pattern, function (array $matches) {
            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

            return $this->componentString($matches[1], $attributes);
        }, $value);
    }

    /**
     * Compile the self-closing tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileSelfClosingTags(string $value)
    {
        $pattern = "/
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                \s*
                (?<attributes>
                    (?:
                        \s+
                        [\w\-:.@]+
                        (
                            =
                            (?:
                                \\\"[^\\\"]*\\\"
                                |
                                \'[^\']*\'
                                |
                                [^\'\\\"=<>]+
                            )
                        )?
                    )*
                    \s*
                )
            \/>
        /x";

        return preg_replace_callback($pattern, function (array $matches) {
            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

            return $this->componentString($matches[1], $attributes) . "\n@endcomponentClass";
        }, $value);
    }

    /**
     * Compile the Blade component string for the given component and attributes.
     *
     * @param  string  $component
     * @param  array  $attributes
     * @return string
     */
    protected function componentString(string $component, array $attributes)
    {
        $class = $this->componentClass($component);

        [$data, $attributes] = $this->partitionDataAndAttributes($class, $attributes);

        $data = $data->mapWithKeys(function ($value, $key) {
            return [Str::camel($key) => $value];
        });

        // If the component doesn't exists as a class we'll assume it's a class-less
        // component and pass the component as a view parameter to the data so it
        // can be accessed within the component and we can render out the view.
        if (!class_exists($class)) {
            $parameters = [
                'view' => "'$class'",
                'data' => '[' . $this->attributesToString($data->all()) . ']'
            ];

            $class = AnonymousComponent::class;
        } else {
            $parameters = $data->all();
        }

        return " @component('{$class}', [" . $this->attributesToString($parameters) . '])
<?php $component->withAttributes([' . $this->attributesToString($attributes->all()) . ']); ?>';
    }

    /**
     * Get the component class for a given component alias.
     *
     * @param  string  $component
     * @return string
     */
    protected function componentClass(string $component)
    {
        if (isset($this->aliases[$component])) {
            return $this->aliases[$component];
        }

        throw new InvalidArgumentException(
            "Unable to locate a class or view for component [{$component}]."
        );
    }

    /**
     * Partition the data and extra attributes from the given array of attributes.
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return array
     */
    protected function partitionDataAndAttributes($class, array $attributes)
    {
        // If the class doesn't exists, we'll assume it's a class-less component and
        // return all of the attributes as both data and attributes since we have
        // now way to partition them. The user can exclude attributes manually.
        if (!class_exists($class)) {
            return [new Collection($attributes), new Collection($attributes)];
        }

        $constructor = (new ReflectionClass($class))->getConstructor();

        $parameterNames = $constructor
        ? (new Collection($constructor->getParameters()))->map->getName()->all()
        : [];

        return (new Collection($attributes))->partition(function ($value, $key) use ($parameterNames) {
            return in_array(Str::camel($key), $parameterNames);
        });
    }

    /**
     * Compile the closing tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileClosingTags(string $value)
    {
        return preg_replace("/<\/\s*x[-\:][\w\-\:\.]*\s*>/", ' @endcomponentClass', $value);
    }

    /**
     * Compile the slot tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    public function compileSlots(string $value)
    {
        $value = preg_replace_callback('/<\s*x[\-\:]slot\s+name=(?<name>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+))\s*>/', function ($matches) {
            return " @slot('" . $this->stripQuotes($matches['name']) . "') ";
        }, $value);

        return preg_replace('/<\/\s*x[\-\:]slot[^>]*>/', ' @endslot', $value);
    }

    /**
     * Get an array of attributes from the given attribute string.
     *
     * @param  string  $attributeString
     * @return array
     */
    protected function getAttributesFromAttributeString(string $attributeString)
    {
        $attributeString = $this->parseBindAttributes($attributeString);

        $pattern = '/
            (?<attribute>[\w\-:.@]+)
            (
                =
                (?<value>
                    (
                        \"[^\"]+\"
                        |
                        \\\'[^\\\']+\\\'
                        |
                        [^\s>]+
                    )
                )
            )?
        /x';

        if (!preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return (new Collection($matches))->mapWithKeys(function ($match) {
            $attribute = $match['attribute'];
            $value = $match['value'] ?? null;

            if (is_null($value)) {
                $value = 'true';

                $attribute = Str::start($attribute, 'bind:');
            }

            $value = $this->stripQuotes($value);

            if (Str::startsWith($attribute, 'bind:')) {
                $attribute = Str::after($attribute, 'bind:');
            } else {
                $value = "'" . str_replace("'", "\\'", $value) . "'";
            }

            return [$attribute => $value];
        })->toArray();
    }

    /**
     * Parse the "bind" attributes in a given attribute string into their fully-qualified syntax.
     *
     * @param  string  $attributeString
     * @return string
     */
    protected function parseBindAttributes(string $attributeString)
    {
        $pattern = "/
            (?:^|\s+)     # start of the string or whitespace between attributes
            :             # attribute needs to start with a semicolon
            ([\w\-:.@]+)  # match the actual attribute name
            =             # only match attributes that have a value
        /xm";

        return preg_replace($pattern, ' bind:$1=', $attributeString);
    }

    /**
     * Convert an array of attributes to a string.
     *
     * @param  array  $attributes
     * @return string
     */
    protected function attributesToString(array $attributes)
    {
        return (new Collection($attributes))
            ->map(function (string $value, string $attribute) {
                return "'{$attribute}' => {$value}";
            })
            ->implode(',');
    }

    /**
     * Strip any quotes from the given string.
     */
    public function stripQuotes(string $value)
    {
        return Str::startsWith($value, ['"', '\''])
        ? substr($value, 1, -1)
        : $value;
    }
}
