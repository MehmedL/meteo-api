<?php

class UserCredentialsDao extends BaseDao
{
    protected string $table = 'usercredentials';
    protected string $dtoClass = UserCredentialsDto::class;
}
