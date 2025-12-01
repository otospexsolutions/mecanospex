declare namespace App.Shared.Application.DTOs {
export type PaginationData = {
page: number;
perPage: number;
total: number;
totalPages: number;
hasNextPage: boolean;
hasPreviousPage: boolean;
};
}
