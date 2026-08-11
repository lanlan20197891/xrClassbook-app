import { pgTable, serial, timestamp, unique, varchar, text, jsonb, foreignKey, integer, real, date } from "drizzle-orm/pg-core"
import { sql } from "drizzle-orm"



export const healthCheck = pgTable("health_check", {
	id: serial().notNull(),
	updatedAt: timestamp("updated_at", { withTimezone: true, mode: 'string' }).defaultNow(),
});

export const users = pgTable("users", {
	id: serial().primaryKey().notNull(),
	username: varchar({ length: 100 }).notNull(),
	password: varchar({ length: 255 }).default('').notNull(),
	headUrl: text("head_url").default(''),
	status: varchar({ length: 10 }).default('On').notNull(),
	userGroup: varchar("user_group", { length: 50 }).default('Student').notNull(),
	userData: jsonb("user_data").default({}),
	loginIp: varchar("login_ip", { length: 50 }).default(''),
	loginDate: timestamp("login_date", { mode: 'string' }),
	token: varchar({ length: 255 }).default(''),
	createdAt: timestamp("created_at", { mode: 'string' }).default(sql`CURRENT_TIMESTAMP`),
}, (table) => [
	unique("users_username_key").on(table.username),
]);

export const moonRelations = pgTable("moon_relations", {
	id: serial().primaryKey().notNull(),
	userId: integer("user_id").notNull(),
	targetId: integer("target_id").default(0).notNull(),
	category: varchar({ length: 20 }).default('classmate').notNull(),
	posX: real("pos_x"),
	posY: real("pos_y"),
	customName: varchar("custom_name", { length: 50 }).default('').notNull(),
	customNote: varchar("custom_note", { length: 200 }).default('').notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default(sql`CURRENT_TIMESTAMP`),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default(sql`CURRENT_TIMESTAMP`),
}, (table) => [
	foreignKey({
			columns: [table.userId],
			foreignColumns: [users.id],
			name: "moon_relations_user_id_fkey"
		}),
	unique("moon_relations_user_id_target_id_key").on(table.userId, table.targetId),
]);

export const moonAlbums = pgTable("moon_albums", {
	id: serial().primaryKey().notNull(),
	userId: integer("user_id").notNull(),
	name: varchar({ length: 50 }).notNull(),
	description: text().default(''),
	createdAt: timestamp("created_at", { mode: 'string' }).default(sql`CURRENT_TIMESTAMP`),
}, (table) => [
	foreignKey({
			columns: [table.userId],
			foreignColumns: [users.id],
			name: "moon_albums_user_id_fkey"
		}),
]);

export const moonPhotos = pgTable("moon_photos", {
	id: serial().primaryKey().notNull(),
	userId: integer("user_id").notNull(),
	fileName: varchar("file_name", { length: 255 }).notNull(),
	originalName: varchar("original_name", { length: 255 }).default('').notNull(),
	url: text().notNull(),
	title: varchar({ length: 100 }).default(''),
	description: text().default(''),
	albumId: integer("album_id").default(0).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default(sql`CURRENT_TIMESTAMP`),
}, (table) => [
	foreignKey({
			columns: [table.userId],
			foreignColumns: [users.id],
			name: "moon_photos_user_id_fkey"
		}),
]);

export const imageTimeline = pgTable("image_timeline", {
	id: serial().primaryKey().notNull(),
	dirId: integer("dir_id").notNull(),
	title: varchar({ length: 255 }).default('').notNull(),
	description: text().default(''),
	dateLabel: varchar("date_label", { length: 50 }).default(''),
	sortDate: date("sort_date"),
	imageUrl: text("image_url").notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default(sql`CURRENT_TIMESTAMP`),
});
