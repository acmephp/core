<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Http;

use AcmePhp\Core\Http\Base64SafeEncoder;

class Base64SafeEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestVectors
     */
    public function testEncodeAndDecode($message, $expected_result, $use_padding = false)
    {
        $encoded = Base64SafeEncoder::encode($message, $use_padding);
        $decoded = Base64SafeEncoder::decode($expected_result);

        $this->assertEquals($expected_result, $encoded);
        $this->assertEquals($message, $decoded);
    }

    /**
     * @see https://tools.ietf.org/html/rfc4648#section-10
     */
    public function getTestVectors()
    {
        return [
            [
                '000000', 'MDAwMDAw',
            ],
            [
                "\0\0\0\0", 'AAAAAA',
            ],
            [
                "\xff", '_w',
            ],
            [
                "\xff\xff", '__8',
            ],
            [
                "\xff\xff\xff", '____',
            ],
            [
                "\xff\xff\xff\xff", '_____w',
            ],
            [
                "\xfb", '-w',
            ],
            [
                '', '',
            ],
            [
                'foo', 'Zm9v', true,
            ],
            [
                'foobar', 'Zm9vYmFy', true,
            ],
        ];
    }

    /**
     * @dataProvider getTestBadVectors
     */
    public function testBadInput($input)
    {
        $decoded = Base64SafeEncoder::decode($input);
        $this->assertEquals("\00", $decoded);
    }

    public function getTestBadVectors()
    {
        return [
            [
                ' AA',
            ],
            [
                "\tAA",
            ],
            [
                "\rAA",
            ],
            [
                "\nAA",
            ],
        ];
    }
}
