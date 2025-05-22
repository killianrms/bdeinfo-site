<div class="container">
    <h1 class="page-title">Contact</h1>
    
    <div class="contact-container">
        <div class="contact-info">
            <h2>Nous contacter</h2>
            <p>Vous avez des questions, des suggestions ou vous souhaitez simplement nous dire bonjour ? N'hésitez pas à nous contacter !</p>
            
            <div class="contact-methods">
                <div class="contact-method">
                    <i class="bi bi-envelope-fill"></i>
                    <h3>Email</h3>
                    <p><a href="mailto:contact@bdeinfo.fr">contact@bdeinfo.fr</a></p>
                </div>
                
                <div class="contact-method">
                    <i class="bi bi-discord"></i>
                    <h3>Discord</h3>
                    <p>Rejoignez notre <a href="https://discord.gg/bdeinfo" target="_blank">serveur Discord</a></p>
                </div>
                
                <div class="contact-method">
                    <i class="bi bi-geo-alt-fill"></i>
                    <h3>Adresse</h3>
                    <p>Campus Universitaire<br>Bâtiment Informatique, Salle B204<br>75000 Paris</p>
                </div>
            </div>
        </div>
        
        <div class="contact-form">
            <h2>Formulaire de contact</h2>
            <form action="/contact/submit" method="post">
                <div class="form-group">
                    <label for="name">Nom complet</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Sujet</label>
                    <select id="subject" name="subject" required>
                        <option value="">Choisir un sujet</option>
                        <option value="question">Question générale</option>
                        <option value="event">Question sur un événement</option>
                        <option value="membership">Question sur l'adhésion</option>
                        <option value="partnership">Proposition de partenariat</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.contact-container {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    margin-top: 30px;
}

.contact-info, .contact-form {
    flex: 1;
    min-width: 300px;
}

.contact-methods {
    margin-top: 30px;
}

.contact-method {
    margin-bottom: 25px;
    padding-left: 15px;
    border-left: 3px solid var(--primary-color);
}

.contact-method i {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.contact-method h3 {
    margin: 5px 0;
    font-size: 1.2rem;
}

.contact-form form {
    background-color: var(--card-bg);
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background-color: var(--input-bg);
    color: var(--text-color);
    font-family: inherit;
}

.form-group textarea {
    resize: vertical;
}

.form-group button {
    padding: 12px 24px;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .contact-container {
        flex-direction: column;
    }
}
</style>