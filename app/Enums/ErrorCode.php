<?php

namespace App\Enums;

enum ErrorCode: string
{
    case Unauthenticated = 'unauthenticated';
    case Forbidden = 'forbidden';
    case NotFound = 'not_found';
    case RouteNotFound = 'route_not_found';
    case ParentDeleted = 'parent_deleted';
    case ParentArchived = 'parent_archived';
    case LastList = 'last_list';
    case IdConflict = 'id_conflict';
    case TripChecklist = 'trip_checklist';
    case SharedSpaceExists = 'shared_space_exists';
    case ResourceDeleted = 'resource_deleted';
    case ValidationFailed = 'validation_failed';
    case InvalidCredentials = 'invalid_credentials';
    case InvitationInvalid = 'invitation_invalid';
    case TooManyRequests = 'too_many_requests';
    case ServerError = 'server_error';

    /**
     * The HTTP status the contract assigns to this code.
     */
    public function status(): int
    {
        return match ($this) {
            self::Unauthenticated => 401,
            self::Forbidden => 403,
            self::NotFound, self::RouteNotFound => 404,
            self::ParentDeleted, self::ParentArchived, self::LastList, self::IdConflict, self::TripChecklist, self::SharedSpaceExists => 409,
            self::ResourceDeleted => 410,
            self::ValidationFailed, self::InvalidCredentials, self::InvitationInvalid => 422,
            self::TooManyRequests => 429,
            self::ServerError => 500,
        };
    }
}
