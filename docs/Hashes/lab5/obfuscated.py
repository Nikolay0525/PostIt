# Laboratory work #5 — obfuscated program (Appendix A, Example #2 style).
# Same logic as original.py: reads a key and grants access only for Key #1.
# Key #1 never appears; the granted branch is an AES-GCM-encrypted payload whose
# key is derived from Key #1, executed only if decryption authenticates. Control
# flow is flattened through a state machine with opaque predicates and junk states.

import hashlib
import builtins

from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from cryptography.exceptions import InvalidTag


def _q7(x):
    b = x.encode("utf-8")
    return (
        bytes(
            (v ^ ((i * 29 + 0x53) & 0xff))
            for i, v in enumerate(b)
        )
        + bytes(reversed(b))
        + len(b).to_bytes(2, "big")
    )


def _m2a(h):
    return (0x92, 0x6D)[h[0:16] == "12dbdfb28f744132"]


def _x91(h):
    return (0xC8, 0x37)[h[32:48] == "045844c43ace1bcc"]


def _r4c(h):
    return (0x5E, 0xA1)[h[16:32] == "011ff19101bc8ebc"]


def _k0f(h):
    return (0x37, 0xC8)[h[48:64] == "af83ff13a231f516"]


def _v31():
    z = (
        b"\x13\xa9",
        b"p7",
        b"lic",
        b"2026",
    )

    return z[2] + z[1] + z[3] + z[0]


def _c8(x):
    h = hashlib.sha256(
        x.encode("utf-8")
    ).hexdigest()

    m = bytes((
        _m2a(h),
        _x91(h),
        _r4c(h),
        _k0f(h),
    ))

    r = hashlib.sha256(
        _q7(x)
        + _v31()
        + m
    ).digest()

    return hashlib.sha256(
        r
        + b"ok"
        + b"run"
        + b"\x88\x11"
        + b"\xa5"
    ).digest()


def _n4():
    return hashlib.sha256(
        b"\xa4\x13"
        + _v31()
        + b"deny-vm"
        + bytes.fromhex("91a7c30d55e2")
    ).digest()


def _j3():
    x = (
        b"5-",
        b"vm",
        b"lab",
    )

    return x[2] + x[0] + x[1]


def _t6(x):
    return ((x * x + x) & 1) == 0


def _h4(a, b):
    return bytes(
        x ^ y
        for x, y in zip(a, b)
    )


def _d2(o):
    return getattr(
        o,
        "".join(
            map(
                chr,
                (100, 101, 99, 114, 121, 112, 116)
            )
        )
    )


def _e5(x):
    getattr(
        builtins,
        "".join(
            map(
                chr,
                (101, 120, 101, 99)
            )
        )
    )(
        x.decode("utf-8"),
        {
            "__builtins__": {
                "print": print
            }
        }
    )


def _z8(k):
    if k:
        return _h4(
            bytes.fromhex(
                "13579bdf2468ace013579bdf"
            ),
            bytes.fromhex(
                "6f49b1927b08dd6280f32e19"
            )
        )

    return _h4(
        bytes.fromhex(
            "2468ace013579bdf2468ace0"
        ),
        bytes.fromhex(
            "b5ca1f24c6b16cd73d4297ac"
        )
    )


_u = input("Введіть пароль: ")

_a = {}
_b = {}

_g = 0x6D ^ 0x54
_s = 0x21 ^ 0x21


while True:

    _w = (
        (_s * _s)
        ^ (_s >> 1)
        ^ _s
        ^ (0x12 ^ 0x13)
    ) & (0x27 ^ 0x28)

    match _w:

        case 0:
            if _t6(_g):
                _b[3] = bytes.fromhex(
                    "430634d05e7977b441460f549d288f81"
                )
            else:
                _b[3] = hashlib.sha256(
                    bytes((_g & 0xff,))
                ).digest()[:16]

        case 1:
            _a[0] = (
                bytes.fromhex(
                    "021ff49e2fca2ccaaec86a"
                )
                if _t6(_s)
                else b"\x00" * 11
            )

        case 2:
            _a[2] = bytes.fromhex(
                "af27e48a72cec8f30f"
            )

        case 4:
            _a[1] = bytes.fromhex(
                "cb19a3ab579f48772270c74f69f73bfc27"
            )

        case 5:
            _b[0] = bytes.fromhex(
                "9cfeb101fa158d0131388c7161"
            )

        case 6:
            _b[1] = bytes.fromhex(
                "f872769fcbdc7a99"
            )

        case 7:
            try:
                _p = _d2(
                    AESGCM(_c8(_u))
                )(
                    _z8(1),
                    b"".join(
                        _a[i ^ 0]
                        for i in (0, 1, 2, 3)
                    ),
                    _j3()
                )

                _e5(_p)
                break

            except InvalidTag:
                pass

        case 8:
            if ((_g ^ _g) | int(_t6(_s))) == 1:
                _b[2] = bytes.fromhex(
                    "f3cef56317d0d616cf054d1415ae2de8e5aa7cd5ff"
                )
            else:
                _b[2] = b"\xff" * 21

        case 9:
            _a[3] = bytes.fromhex(
                "6e3a9841c6af63b62ed3a9d611f74e708edb1d"
            )

        case 10:
            _p = _d2(
                AESGCM(_n4())
            )(
                _z8(0),
                b"".join(
                    _b[(i << 1) >> 1]
                    for i in (0, 1, 2, 3)
                ),
                _j3()
            )

            _e5(_p)
            break

        case 11:
            _g = (
                (_g << 3)
                ^ (_g >> 2)
                ^ (0x31B4 ^ 0x6BC5)
            ) & 0xFFFF

        case 15:
            _g ^= int.from_bytes(
                hashlib.sha256(
                    bytes((_g, _w))
                ).digest()[:2],
                "big"
            )

        case _:
            _g ^= (
                (_w + 3)
                * (0x54 ^ 0x65)
            ) & 0xFFFF

    _s = (
        (_s ^ 7) * 3 + 1
    ) & 0xFF