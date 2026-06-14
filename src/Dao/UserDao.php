<?php

class UserDao extends BaseDao
{
    protected string $table = 'user';
    protected string $dtoClass = UserDto::class;
}
