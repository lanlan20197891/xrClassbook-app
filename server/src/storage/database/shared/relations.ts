import { relations } from "drizzle-orm/relations";
import { users, moonRelations, moonAlbums, moonPhotos } from "./schema";

export const moonRelationsRelations = relations(moonRelations, ({one}) => ({
	user: one(users, {
		fields: [moonRelations.userId],
		references: [users.id]
	}),
}));

export const usersRelations = relations(users, ({many}) => ({
	moonRelations: many(moonRelations),
	moonAlbums: many(moonAlbums),
	moonPhotos: many(moonPhotos),
}));

export const moonAlbumsRelations = relations(moonAlbums, ({one}) => ({
	user: one(users, {
		fields: [moonAlbums.userId],
		references: [users.id]
	}),
}));

export const moonPhotosRelations = relations(moonPhotos, ({one}) => ({
	user: one(users, {
		fields: [moonPhotos.userId],
		references: [users.id]
	}),
}));