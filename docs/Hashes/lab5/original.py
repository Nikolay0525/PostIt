#!/usr/bin/env python3
# Laboratory work #5 — original (reference) program.
# Logic analogous to the initial program of Appendix A, but the key in the
# comparison condition is Key #1 instead of "Secret123".

password = input("Введіть пароль: ")

if password == "2026_SE-23-1_Poberezhnyi_Mykola_варіант:12_":   # Key #1
    print("Доступ дозволено")
else:
    print("Доступ заборонено")
